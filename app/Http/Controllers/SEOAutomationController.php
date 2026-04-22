<?php

namespace App\Http\Controllers;

use App\Helpers\GeneralHelper;
use App\Models\CoreWebVital;
use App\Models\RankingCompetitorReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SEOAutomationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function rankingCompetitorReport($domainmanagement_id, $client_property_id)
    {
        $domain = GeneralHelper::getDomainByClientPropertyId($client_property_id);

        $user = auth()->user();

        // Admin / Manager  → all their reports across every client
        // Everyone else    → only reports for this specific client_property_id
        $query = RankingCompetitorReport::where('user_id', $user->id)
                                        ->orderBy('created_at', 'desc');

        if (!in_array(optional($user->role)->name, ['admin', 'manager'])) {
            $query->where('client_property_id', $client_property_id);
        }

        $savedReports = $query->get()->map(fn ($r) => [
            'id'            => $r->id,
            'label'         => $r->dropdown_label,
            'location'      => $r->location,
            'keywords'      => $r->keywords,
            'client_domain' => $r->client_domain,
            'competitor_option' => $r->competitor_option,
            'results_json'  => $r->results_json,
        ]);

        return view(
            'arihant.seo.ranking_competitor_report',
            compact('domainmanagement_id', 'client_property_id', 'domain', 'savedReports')
        );
    }

    public function rankingCompetitorReportForm(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'keyword'  => 'required|string|max:255',
            'location' => 'required|string',
        ]);
    
        $keyword  = trim($request->keyword);
        $location = trim($request->location);
    
        // Pass location into the helper (you may extend GeneralHelper to accept it)
        $searchJson = GeneralHelper::getSearchResult($keyword, 'google', $location);
        $searchData = json_decode($searchJson, true);
    
        if (!$searchData || isset($searchData['error'])) {
            return response()->json([
                'error'   => true,
                'message' => $searchData['error'] ?? 'Failed to fetch results',
            ], 422);
        }
    
        return response()->json($searchData);
    }


    public function saveRankingCompetitorReport(Request $request)
    {
        $request->validate([
            'domainmanagement_id' => 'required|integer',
            'client_property_id'  => 'required|integer',
            'location'            => 'required|string|max:100',
            'keywords'            => 'required|array|min:1',
            'keywords.*'          => 'string|max:255',
            'client_domain'       => 'nullable|string|max:255',
            'competitor_option'   => 'nullable|string|in:auto,define',
            'results_json'        => 'required|array',
        ]);

        $report = RankingCompetitorReport::create([
            'domainmanagement_id' => $request->domainmanagement_id,
            'client_property_id'  => $request->client_property_id,
            'user_id'             => auth()->id(),
            'location'            => $request->location,
            'keywords'            => $request->keywords,
            'client_domain'       => $request->client_domain,
            'competitor_option'   => $request->competitor_option,
            'results_json'        => $request->results_json,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report saved successfully.',
            'report'  => [
                'id'                => $report->id,
                'label'             => $report->dropdown_label,
                'location'          => $report->location,
                'keywords'          => $report->keywords,
                'client_domain'     => $report->client_domain,
                'competitor_option' => $report->competitor_option,
                'results_json'      => $report->results_json,
            ],
        ]);
    }

    
    public function coreWebVitals($domainmanagement_id, $client_property_id)
    {
        $domain = GeneralHelper::getDomainByClientPropertyId($client_property_id);
        // dd(Auth::user()->id);
        $query = CoreWebVital::where('domainmanagement_id', $domainmanagement_id)
            ->where('client_property_id', $client_property_id)
            ->orderByDesc('created_at');
    
        // Admin / Manager see all records; everyone else sees only their own
        if (! Auth::user()->role->name == 'admin' && ! Auth::user()->role->name == 'manager') {
            $query->where('user_id', Auth::user()->id);
        }
    
        $savedResults = $query->get();
    
        return view(
            'arihant.seo.core_web_vitals',
            compact('domainmanagement_id', 'client_property_id', 'domain', 'savedResults')
        );
    }

    public function coreWebVitalsform(Request $request)
    {
        $request->validate([
            'ourclient'              => 'required|url',
            'domainmanagement_id'    => 'required|integer',
            'client_property_id'     => 'required|integer',
        ]);

        $url    = rtrim($request->input('ourclient'), '/');
        $apiKey = env('PAGESPEED_API_KEY');

        $results = [];

        foreach (['mobile', 'desktop'] as $strategy) {
            $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?' . http_build_query([
                'url'      => $url,
                'key'      => $apiKey,
                'strategy' => $strategy,
                'category' => 'performance',
            ]);

            $response = Http::timeout(60)->get($apiUrl);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'PageSpeed API request failed for strategy: ' . $strategy
                            . ' — ' . $response->status(),
                ], 502);
            }

            $data       = $response->json();
            $audits     = $data['lighthouseResult']['audits']     ?? [];
            $categories = $data['lighthouseResult']['categories'] ?? [];

            // Helper: pull display value + numeric value from an audit node
            $audit = fn(string $key) => [
                'display' => $audits[$key]['displayValue'] ?? '—',
                'value'   => $audits[$key]['numericValue'] ?? null,
                'score'   => $audits[$key]['score']        ?? null,
            ];

            $results[$strategy] = [
                'performance_score' => (int) round(($categories['performance']['score'] ?? 0) * 100),
                'fcp'               => $audit('first-contentful-paint'),
                'lcp'               => $audit('largest-contentful-paint'),
                'tbt'               => $audit('total-blocking-time'),
                'cls'               => $audit('cumulative-layout-shift'),
                'si'                => $audit('speed-index'),
                'tti'               => $audit('interactive'),
                'ttfb'              => $audit('server-response-time'),
                'inp'               => $audit('interaction-to-next-paint'),
            ];
        }

        // ── Build flat DB row from both strategies ──────────────────────
        $row = [
            'domainmanagement_id' => $request->input('domainmanagement_id'),
            'client_property_id'  => $request->input('client_property_id'),
            'user_id'             => Auth::user()->id,
            'url'                 => $url,
        ];

        foreach (['mobile', 'desktop'] as $strategy) {
            $s = $results[$strategy];
            $row["{$strategy}_performance_score"] = $s['performance_score'];

            foreach (['fcp', 'lcp', 'tbt', 'cls', 'si', 'tti', 'ttfb', 'inp'] as $metric) {
                $row["{$strategy}_{$metric}_display"] = $s[$metric]['display'];
                $row["{$strategy}_{$metric}_value"]   = $s[$metric]['value'];
                $row["{$strategy}_{$metric}_score"]   = $s[$metric]['score'];
            }
        }

        CoreWebVital::create($row);
        // ───────────────────────────────────────────────────────────────

        return response()->json([
            'success' => true,
            'url'     => $url,
            'data'    => $results,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
