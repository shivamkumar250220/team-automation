<?php

namespace App\Services\GMB;

use App\Models\Client;
use App\Models\GmbLocation;
use App\Models\GmbReview;
use App\Models\GmbReviewAlert;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class GmbSentimentAlertService
{
    public function runForAllClients(): void
    {
        $clients = Client::where('team_id', 2)
            ->where('status', 'active')
            ->get();

        foreach ($clients as $client) {
            try {
                $this->processClient($client);
            } catch (\Exception $e) {
                Log::error("Sentiment alert failed for client {$client->id}: " . $e->getMessage());
            }
        }
    }

    public function processClient(Client $client): void
    {
        $location = GmbLocation::where('client_id', $client->id)
            ->active()
            ->first();

        if (!$location) return;

        // Already alerted review IDs
        $alreadyAlerted = GmbReviewAlert::where('client_id', $client->id)
            ->pluck('gmb_review_id')
            ->toArray();

        // Negative reviews jo abhi tak alert nahi hue
        $negativeReviews = GmbReview::where('gmb_location_id', $location->id)
            ->whereIn('rating', ['ONE', 'TWO'])
            ->whereNotIn('id', $alreadyAlerted)
            ->get();

        foreach ($negativeReviews as $review) {
            $draft = $this->generateDraftReply(
                $review->reviewer_name ?? 'Patient',
                $review->comment ?? '',
                $review->starCount(),
                $client->name
            );

            GmbReviewAlert::create([
                'client_id'       => $client->id,
                'gmb_location_id' => $location->id,
                'gmb_review_id'   => $review->id,
                'review_id'       => $review->review_id,
                'reviewer_name'   => $review->reviewer_name,
                'rating'          => $review->rating,
                'comment'         => $review->comment,
                'draft_reply'     => $draft,
                'alert_sent_at'   => now(),
            ]);

            $this->sendEmailAlert($client, $review, $draft);
            $this->sendChatAlert($client, $review, $draft);
        }
    }

    private function generateDraftReply(
        string $reviewerName,
        string $reviewText,
        int $starRating,
        string $clinicName
    ): string {
        $apiKey = config('gmb.gemini_api_key');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model'       => 'gpt-4o-mini',
                'temperature' => 0.7,
                'max_tokens'  => 300,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'You are a professional healthcare clinic response writer.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => "Write one empathetic reply for this {$starRating}-star review for clinic '{$clinicName}'. Reviewer: {$reviewerName}. Review: \"{$reviewText}\". Keep it 2-3 sentences. Acknowledge concern, offer to resolve offline.",
                    ],
                ],
            ]);

            return $response->json('choices.0.message.content', 'Draft unavailable.');
        } catch (\Exception $e) {
            Log::error('Draft generation failed: ' . $e->getMessage());
            return 'Draft unavailable.';
        }
    }

    private function sendEmailAlert(Client $client, GmbReview $review, string $draft): void
    {
        $tlEmail = config('gmb.tl_email');
        if (!$tlEmail) return;

        try {
            Mail::raw(
                "NEGATIVE REVIEW ALERT\n\n" .
                "Client: {$client->name}\n" .
                "Reviewer: {$review->reviewer_name}\n" .
                "Rating: {$review->starCount()} star\n\n" .
                "Review:\n{$review->comment}\n\n" .
                "Suggested Reply:\n{$draft}",
                function (Message $msg) use ($tlEmail, $client) {
                    $msg->to($tlEmail)
                        ->subject("Negative Review — {$client->name} — Action Needed");
                }
            );
        } catch (\Exception $e) {
            Log::error('Email alert failed: ' . $e->getMessage());
        }
    }

    private function sendChatAlert(Client $client, GmbReview $review, string $draft): void
    {
        $webhook = config('gmb.chat_webhook_url');
        if (!$webhook) return;

        try {
            Http::post($webhook, [
                'text' =>
                    "*Negative Review — {$client->name}*\n" .
                    "Reviewer: {$review->reviewer_name} | Rating: {$review->starCount()} star\n\n" .
                    "*Review:*\n{$review->comment}\n\n" .
                    "*Draft Reply:*\n{$draft}",
            ]);
        } catch (\Exception $e) {
            Log::error('Chat alert failed: ' . $e->getMessage());
        }
    }
}