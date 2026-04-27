<?php

namespace App\Services\GMB;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GmbGeminiService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('gmb.gemini_api_key');
    }

    public function generateDrafts(string $reviewerName, string $reviewText, int $starRating, string $clinicName): array
    {
        $prompt = $this->buildPrompt($reviewerName, $reviewText, $starRating, $clinicName);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->post($this->apiUrl, [
                'model'       => 'gpt-4o-mini',
                'temperature' => 0.8,
                'max_tokens'  => 2000,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'You are a professional healthcare clinic response writer. Follow the format exactly as instructed.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::error('OpenAI API failed: ' . $response->body());
                return [];
            }

            $text = $response->json('choices.0.message.content', '');

            return $this->parseDrafts($text);

        } catch (\Exception $e) {
            Log::error('OpenAI service error: ' . $e->getMessage());
            return [];
        }
    }

    private function buildPrompt(string $reviewerName, string $reviewText, int $starRating, string $clinicName): string
    {
        $sentiment = $starRating <= 2 ? 'negative' : ($starRating === 3 ? 'neutral' : 'positive');

        return <<<PROMPT
You are a professional healthcare clinic response writer for "{$clinicName}".

A patient left a {$starRating}-star ({$sentiment}) review:
Reviewer: {$reviewerName}
Review: "{$reviewText}"

Write exactly 5 different response drafts, each with a different tone:
Draft 1: Warm and empathetic
Draft 2: Professional and formal  
Draft 3: Friendly and conversational
Draft 4: Concise and direct
Draft 5: Detailed and reassuring

Rules:
- Each response must be 2-4 sentences
- Address the reviewer by first name
- For negative reviews, acknowledge the concern and offer to resolve offline
- Do not make specific medical promises
- End with clinic name

Format your response EXACTLY like this (no extra text):
DRAFT_1: [response here]
DRAFT_2: [response here]
DRAFT_3: [response here]
DRAFT_4: [response here]
DRAFT_5: [response here]
PROMPT;
    }

    private function parseDrafts(string $text): array
    {
        $drafts = [];

        preg_match_all('/DRAFT_(\d):\s*(.+?)(?=DRAFT_\d:|$)/s', $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $index          = (int) $match[1];
            $drafts[$index] = trim($match[2]);
        }

        return $drafts;
    }
}