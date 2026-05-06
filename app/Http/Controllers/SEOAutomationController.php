<?php

namespace App\Http\Controllers;

use App\Helpers\GeneralHelper;
use App\Models\Client_propertiesModel;
use App\Models\CoreWebVital;
use App\Models\RankingCompetitorReport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Google\Service\Sheets\Spreadsheet;
use Google\Service\Sheets\SpreadsheetProperties;
use Google\Service\Sheets\Sheet;
use Google\Service\Sheets\SheetProperties;
use Google\Service\Sheets\GridProperties;
use Google\Service\Sheets\Request as SheetsRequest;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\ValueRange;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;

class SEOAutomationController extends Controller
{
    public function index() {}

    public function rankingCompetitorReport($created_by_user_id, $client_property_id)
    {
        $domain = GeneralHelper::getDomainByClientPropertyId($client_property_id);
        $user   = auth()->user();

        $query = RankingCompetitorReport::where('created_by_user_id', $created_by_user_id)
                                        ->orderBy('created_at', 'desc');

        if (!in_array(optional($user->role)->name, ['admin', 'manager'])) {
            $query->where('client_property_id', $client_property_id);
        }

        $savedReports = $query->get()->map(fn ($r) => [
            'id'                => $r->id,
            'label'             => $r->dropdown_label,
            'location'          => $r->location,
            'keywords'          => $r->keywords,
            'client_domain'     => $r->client_domain,
            'competitor_option' => $r->competitor_option,
            'results_json'      => $r->results_json,
        ]);

        return view(
            'arihant.seo.ranking_competitor_report',
            compact('created_by_user_id', 'client_property_id', 'domain', 'savedReports')
        );
    }
    public function auditCompetitor(Request $request)
{
    try {
        $request->validate([
            'url' => 'required|url'
        ]);

        $response = Http::withToken('1cb22d77fb105a8b929be6bd237eb5ae09abfbf5125e51d3cdeac46d070173ef')
            ->acceptJson()
            ->timeout(180)
            ->post('https://seotech.ichelon.in/api/audit', [
                'url' => $request->url,
                'wait' => true
            ]);

        // dd($response->body()); // better debug

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'API request failed',
                'status'  => $response->status(),
                'body'    => $response->body()
            ], $response->status());
        }
        $data = $response->json();

        return response()->json([
            'success'    => true,
            'data'       => $data['data'] ?? $data,
            'fetched_at' => now()->toISOString()
        ]);

    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        // Catches cURL timeout / connection errors specifically
        return response()->json([ 
            'success' => false,
            'message' => 'Audit timed out. The site may be slow to respond — please try again.',
            'error'   => 'timeout'
        ], 504);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function rankingCompetitorReportForm(Request $request)
    {
        $request->validate([
            'keyword'  => 'required|string|max:255',
            'location' => 'required|string',
        ]);

        $keyword    = trim($request->keyword);
        $location   = trim($request->location);
        $searchJson = GeneralHelper::getSearchResult($keyword, 'google', $location);
        $searchData = json_decode($searchJson, true);

        if (!$searchData || isset($searchData['error'])) {
            return response()->json([
                'error'   => true,
                'message' => $searchData['error'] ?? 'Failed to fetch results',
            ], 422);
        }

        $aiOverview = null;

        if (isset($searchData['ai_overview'])) {
            if (!isset($searchData['ai_overview']['page_token'])) {
                $aiOverview = $searchData['ai_overview'];
            } else {
                $aioJson    = GeneralHelper::getaioResult($searchData['ai_overview']['page_token']);
                $aiOverview = json_decode($aioJson, true);
            }
        }

        // Always expose ai_overview at the top level so the frontend reads one key
        $searchData['ai_overview'] = $aiOverview;

        return response()->json($searchData);
    }

    public function saveRankingCompetitorReport(Request $request)
    {
        $request->validate([
            'created_by_user_id' => 'required|integer',
            'client_property_id' => 'required|integer',
            'location'           => 'required|string|max:100',
            'keywords'           => 'required|array|min:1',
            'keywords.*'         => 'string|max:255',
            'client_domain'      => 'nullable|string|max:255',
            'competitor_option'  => 'nullable|string|in:auto,define',
            'results_json'       => 'required|array',
        ]);

        $report = RankingCompetitorReport::create([
            'created_by_user_id' => $request->created_by_user_id,
            'client_property_id' => $request->client_property_id,
            'user_id'            => auth()->id(),
            'location'           => $request->location,
            'keywords'           => $request->keywords,
            'client_domain'      => $request->client_domain,
            'competitor_option'  => $request->competitor_option,
            'results_json'       => $request->results_json,
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

    public function coreWebVitals($created_by_user_id, $client_property_id)
    {
        $domain = GeneralHelper::getDomainByClientPropertyId($client_property_id);

        $query = CoreWebVital::where('created_by_user_id', $created_by_user_id)
            ->where('client_property_id', $client_property_id)
            ->orderByDesc('created_at');

        if (! Auth::user()->role->name == 'admin' && ! Auth::user()->role->name == 'manager') {
            $query->where('created_by_user_id', $created_by_user_id);
        }

        $savedResults = $query->get();

        return view(
            'arihant.seo.core_web_vitals',
            compact('created_by_user_id', 'client_property_id', 'domain', 'savedResults')
        );
    }

    public function coreWebVitalsform(Request $request)
    {
        $request->validate([
            'ourclient'          => 'required|url',
            'created_by_user_id' => 'required|integer',
            'client_property_id' => 'required|integer',
        ]);

        $url    = rtrim($request->input('ourclient'), '/');
        $apiKey = env('PAGESPEED_API_KEY');
        $results = [];

        
        $categories = ['performance', 'accessibility', 'best-practices', 'seo'];

        foreach (['mobile', 'desktop'] as $strategy) {
            $queryParams = [
                'url'      => $url,
                'key'      => $apiKey,
                'strategy' => $strategy,
            ];

            // Manually append categories (important)
            $queryString = http_build_query($queryParams);

            foreach ($categories as $category) {
                $queryString .= '&category=' . urlencode($category);
            }

            $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?' . $queryString;
            Log::info($apiUrl);

            $response = Http::timeout(120)->get($apiUrl);
            // dd($response->body());

            if ($response->failed()) {
                $body = $response->json();
                $googleMessage = $body['error']['message'] ?? $body['error']['errors'][0]['message'] ?? null;
                return response()->json([
                    'error' => 'PageSpeed API request failed for strategy: ' . $strategy . ' — ' . $response->status()
                            . ($googleMessage ? ': ' . $googleMessage : ''),
                ], 502);
            }

            $data       = $response->json();
            $audits     = $data['lighthouseResult']['audits']     ?? [];
            $categories = $data['lighthouseResult']['categories'] ?? [];

            $audit = fn(string $key) => [
                'display' => $audits[$key]['displayValue'] ?? '—',
                'value'   => $audits[$key]['numericValue'] ?? null,
                'score'   => $audits[$key]['score']        ?? null,
            ];

            $results[$strategy] = [
                'performance_score' => (int) round(($categories['performance']['score'] ?? 0) * 100),
                'accessibility_score' => (int) round(($categories['accessibility']['score'] ?? 0) * 100),
                'best_practices_score'=> (int) round(($categories['best-practices']['score'] ?? 0) * 100),
                'seo_score'           => (int) round(($categories['seo']['score'] ?? 0) * 100),
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

        $row = [
            'created_by_user_id' => $request->input('created_by_user_id'),
            'client_property_id' => $request->input('client_property_id'),
            'user_id'            => auth()->id(),
            'url'                => $url,
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

        return response()->json(['success' => true, 'url' => $url, 'data' => $results]);
    }

    // ── REPORTING SHEET ───────────────────────────────────────────────────────────

    public function reportingSheet($created_by_user_id, $client_property_id)
    {
        $domain = GeneralHelper::getDomainByClientPropertyId($client_property_id);

        return view(
            'arihant.seo.reporting_sheet',
            compact('created_by_user_id', 'client_property_id', 'domain')
        );
    }

    public function reportingSheetForm(Request $request)
    {
        $request->validate([
            'ourclient'          => 'required|string',
            'created_by_user_id' => 'required|integer',
            'client_property_id' => 'required|integer',
            'spreadsheet_url'    => 'required|string',
            'xml_files'          => 'nullable|array',
            'xml_files.*'        => 'nullable|file|mimetypes:text/xml,application/xml,text/plain|max:10240',
        ]);

        $domain          = $request->input('ourclient');
        $spreadsheetUrl  = trim($request->input('spreadsheet_url'));

        // ── Extract spreadsheet ID from the URL ─────────────────────────────────
        // Supports: https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit
        //           https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/
        if (!preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9_-]+)/', $spreadsheetUrl, $matches)) {
            return response()->json([
                'success' => false,
                'message' => 'Could not extract a spreadsheet ID from the provided URL. Please check the link.',
            ], 422);
        }

        $spreadsheetId = $matches[1];

        // ── Sheet definitions ─────────────────────────────────────────────────────
        $sheetDefinitions = [
            [
                'name'    => '404',
                'headers' => ['S.No.', 'Link From', 'URL', 'Status Code', 'Link On Text', 'Comments', 'Status'],
            ],
            [
                'name'    => 'Duplicate H1',
                'headers' => ['S.No.', 'Page URL', 'Error', 'Existing H1', 'Recommended H1', 'Comments', 'Status'],
            ],
            [
                'name'    => 'Multiple H1',
                'headers' => ['S.No.', 'Page URL', 'Error', 'H1 Count', 'Comments', 'Status'],
            ],
            [
                'name'    => 'Missing Alt Text',
                'headers' => ['S.No.', 'Link From', 'URL', 'Error', 'Recommended Alt Text', 'Comments', 'Date'],
            ],
            [
                'name'    => 'Oversize Images',
                'headers' => ['S.No', 'Link From', 'URL', 'Size', 'Compressed Image', 'Recommendation', 'Comments'],
            ],
            [
                'name'    => 'Page Speed',
                'headers' => [],
            ],
        ];

        try {
            $client        = $this->getGoogleClient();
            $sheetsService = new Sheets($client);

            // ── Get the existing spreadsheet ────────────────────────────────────────────
            $spreadsheet = $sheetsService->spreadsheets->get($spreadsheetId);
            
            // ── Get existing sheets ─────────────────────────────────────────────────────
            $existingSheets = $spreadsheet->getSheets();
            $batchUpdateRequests = [];

            // Build a map of existing sheet titles => sheetId
            $existingSheetTitles = [];
            foreach ($existingSheets as $sheet) {
                $props = $sheet->getProperties();
                $existingSheetTitles[$props->getTitle()] = $props->getSheetId();
            }

            // ── Ensure "Dashboard" tab exists ──────────────────────────────────────────
            // Case 1: "Dashboard" already exists → nothing to do (we'll clear & rewrite it later)
            // Case 2: "Sheet1" exists → rename it to "Dashboard"
            // Case 3: Neither exists → create a new "Dashboard" tab
            if (!array_key_exists('Dashboard', $existingSheetTitles)) {
                if (array_key_exists('Sheet1', $existingSheetTitles)) {
                    // Rename Sheet1 → Dashboard
                    $batchUpdateRequests[] = new SheetsRequest([
                        'updateSheetProperties' => [
                            'properties' => [
                                'sheetId' => $existingSheetTitles['Sheet1'],
                                'title'   => 'Dashboard',
                            ],
                            'fields' => 'title',
                        ],
                    ]);
                } else {
                    // Create a brand-new Dashboard tab at position 0
                    $batchUpdateRequests[] = new SheetsRequest([
                        'addSheet' => [
                            'properties' => [
                                'title' => 'Dashboard',
                                'index' => 0,
                                'gridProperties' => [
                                    'rowCount'    => 100,
                                    'columnCount' => 3,
                                ],
                            ],
                        ],
                    ]);
                }
            }

            // ── Create any missing data tabs ────────────────────────────────────────────
            foreach ($sheetDefinitions as $def) {
                if (isset($def['note']) || isset($def['docType'])) {
                    continue;
                }

                if (!array_key_exists($def['name'], $existingSheetTitles)) {
                    $batchUpdateRequests[] = new SheetsRequest([
                        'addSheet' => [
                            'properties' => [
                                'title' => $def['name'],
                                'gridProperties' => [
                                    'rowCount'    => 1000,
                                    'columnCount' => 10,
                                ],
                            ],
                        ],
                    ]);
                }
            }

            // ── Execute all add/rename requests BEFORE refreshing sheet IDs ────────────
            if (!empty($batchUpdateRequests)) {
                $sheetsService->spreadsheets->batchUpdate(
                    $spreadsheetId,
                    new BatchUpdateSpreadsheetRequest(['requests' => $batchUpdateRequests])
                );
            }

            // ── Refresh spreadsheet data to get up-to-date sheet IDs ───────────────────
            $updatedSpreadsheet = $sheetsService->spreadsheets->get($spreadsheetId);
            $tabSheetIds = [];
            
            foreach ($updatedSpreadsheet->getSheets() as $sheet) {
                $props = $sheet->getProperties();
                $tabSheetIds[$props->getTitle()] = $props->getSheetId();
            }

            // ── Write header rows into each error tab and style them ────────────────────
            $headerRequests = [];

            foreach ($sheetDefinitions as $def) {
                if (isset($def['note']) || isset($def['docType'])) {
                    continue;
                }

                $tabName = $def['name'];
                $headers = $def['headers'] ?? [$tabName];
                $sheetId = $tabSheetIds[$tabName] ?? null;

                if (!$sheetId) {
                    continue;
                }

                // Clear existing content in the sheet (optional - to start fresh)
                // Get current sheet data to find last row
                $lastRowResponse = $sheetsService->spreadsheets_values->get(
                    $spreadsheetId,
                    $tabName . '!A:Z'
                );
                $existingValues = $lastRowResponse->getValues();
                
                if (!empty($existingValues)) {
                    // Clear all existing content
                    $lastRow = count($existingValues);
                    $sheetsService->spreadsheets_values->clear(
                        $spreadsheetId,
                        $tabName . '!A1:' . $this->getColumnLetter(count($headers)) . $lastRow,
                        new \Google\Service\Sheets\ClearValuesRequest()
                    );
                }

                // Write header row
                $sheetsService->spreadsheets_values->update(
                    $spreadsheetId,
                    $tabName . '!A1',
                    new ValueRange(['values' => [$headers]]),
                    ['valueInputOption' => 'USER_ENTERED']
                );

                // Style the header row
                $numCols = count($headers);
                $headerRequests[] = $this->makeHeaderStyleRequest($sheetId, $numCols);

                // Auto-resize columns
                $headerRequests[] = new SheetsRequest([
                    'autoResizeDimensions' => [
                        'dimensions' => [
                            'sheetId'    => $sheetId,
                            'dimension'  => 'COLUMNS',
                            'startIndex' => 0,
                            'endIndex'   => $numCols,
                        ],
                    ],
                ]);
            }

            if (!empty($headerRequests)) {
                $sheetsService->spreadsheets->batchUpdate(
                    $spreadsheetId,
                    new BatchUpdateSpreadsheetRequest(['requests' => $headerRequests])
                );
            }

            // ── Populate the "404" sheet with broken links from PageSpeed Insights ─────
            $brokenLinks = $this->fetch404BrokenLinks(rtrim($domain, '/'));

            if (!empty($brokenLinks)) {
                $sheetId404 = $tabSheetIds['404'] ?? null;

                if ($sheetId404 !== null) {
                    // Build the value rows: [S.No., Link From, URL, Status Code, Link On Text, Comments, Status]
                    $linkRows = [];
                    foreach ($brokenLinks as $idx => $link) {
                        $linkRows[] = [
                            $idx + 1,
                            $link['linkFrom'],
                            $link['url'],
                            (string) $link['statusCode'],
                            $link['linkOnText'],
                            $link['comments'],
                            '', // Status – left blank for the team to fill in
                        ];
                    }

                    // Write data starting from row 2 (row 1 is the header)
                    $sheetsService->spreadsheets_values->update(
                        $spreadsheetId,
                        '404!A2',
                        new ValueRange(['values' => $linkRows]),
                        ['valueInputOption' => 'USER_ENTERED']
                    );

                    // Green row highlight + auto-resize to match screenshot style
                    $coloringRequests = [];
                    foreach ($linkRows as $rowIdx => $linkRow) {
                        $sheetRowIndex = $rowIdx + 1; // 0-based; row 0 = header, data from index 1
                        $coloringRequests[] = new SheetsRequest([
                            'repeatCell' => [
                                'range' => [
                                    'sheetId'          => $sheetId404,
                                    'startRowIndex'    => $sheetRowIndex,
                                    'endRowIndex'      => $sheetRowIndex + 1,
                                    'startColumnIndex' => 0,
                                    'endColumnIndex'   => 7,
                                ],
                                'cell' => [
                                    'userEnteredFormat' => [
                                        'backgroundColor' => [
                                            'red'   => 0.576,  // #93c47d – same green as screenshot
                                            'green' => 0.769,
                                            'blue'  => 0.49,
                                        ],
                                    ],
                                ],
                                'fields' => 'userEnteredFormat.backgroundColor',
                            ],
                        ]);
                    }

                    // Auto-resize all 7 columns after data is written
                    $coloringRequests[] = new SheetsRequest([
                        'autoResizeDimensions' => [
                            'dimensions' => [
                                'sheetId'    => $sheetId404,
                                'dimension'  => 'COLUMNS',
                                'startIndex' => 0,
                                'endIndex'   => 7,
                            ],
                        ],
                    ]);

                    if (!empty($coloringRequests)) {
                        $sheetsService->spreadsheets->batchUpdate(
                            $spreadsheetId,
                            new BatchUpdateSpreadsheetRequest(['requests' => $coloringRequests])
                        );
                    }
                }
            }


            // ── Gemini AI: Analyse uploaded XML sitemaps ──────────────────────────────
            // If the user uploaded XML sitemap files, send them to Gemini to detect
            // Duplicate H1, Multiple H1, and Missing Alt Text issues.
            $geminiResult = null;
            if ($request->hasFile('xml_files')) {
                $xmlContents = '';
                foreach ($request->file('xml_files') as $xmlFile) {
                    $xmlContents .= "\n\n<!-- Sitemap: " . $xmlFile->getClientOriginalName() . " -->\n";
                    $xmlContents .= file_get_contents($xmlFile->getRealPath());
                }

                if (!empty(trim($xmlContents))) {
                    try {
                        $geminiRaw    = $this->analyzeWithGeminiSeo($domain, $xmlContents);
                        $geminiResult = json_decode($geminiRaw, true);
                        Log::error('Gemini API Response', [
                            'raw_response' => $geminiRaw,
                            'decoded_response' => $geminiResult,
                        ]);
                    } catch (Exception $ge) {
                        Log::error('Gemini SEO analysis error: ' . $ge->getMessage());
                        $geminiResult = null;
                    }
                }
            }

            // ── Populate "Duplicate H1" sheet from Gemini result ─────────────────────
            if (!empty($geminiResult['duplicate_h1'])) {
                $sheetIdDupH1 = $tabSheetIds['Duplicate H1'] ?? null;
                if ($sheetIdDupH1 !== null) {
                    $dupRows = [];
                    foreach ($geminiResult['duplicate_h1'] as $idx => $item) {
                        $h1Text = is_array($item['h1_text']) ? implode(' | ', $item['h1_text']) : ($item['h1_text'] ?? '');
                        $dupRows[] = [
                            $idx + 1,
                            $item['url']         ?? '',
                            'Duplicate H1',
                            $h1Text,
                            '',   // Recommended H1 – left for the team
                            '',   // Comments
                            '',   // Status
                        ];
                    }

                    $sheetsService->spreadsheets_values->update(
                        $spreadsheetId,
                        'Duplicate H1!A2',
                        new ValueRange(['values' => $dupRows]),
                        ['valueInputOption' => 'USER_ENTERED']
                    );

                    $dupRequests = [];
                    foreach ($dupRows as $rowIdx => $_) {
                        $ri = $rowIdx + 1;
                        $dupRequests[] = new SheetsRequest([
                            'repeatCell' => [
                                'range'  => ['sheetId' => $sheetIdDupH1, 'startRowIndex' => $ri, 'endRowIndex' => $ri + 1, 'startColumnIndex' => 0, 'endColumnIndex' => 7],
                                'cell'   => ['userEnteredFormat' => ['backgroundColor' => ['red' => 1.0, 'green' => 0.898, 'blue' => 0.6]]],
                                'fields' => 'userEnteredFormat.backgroundColor',
                            ],
                        ]);
                    }
                    $dupRequests[] = new SheetsRequest(['autoResizeDimensions' => ['dimensions' => ['sheetId' => $sheetIdDupH1, 'dimension' => 'COLUMNS', 'startIndex' => 0, 'endIndex' => 7]]]);
                    $sheetsService->spreadsheets->batchUpdate($spreadsheetId, new BatchUpdateSpreadsheetRequest(['requests' => $dupRequests]));
                }
            }

            // ── Populate "Multiple H1" sheet from Gemini result ──────────────────────
            if (!empty($geminiResult['multiple_h1'])) {
                $sheetIdMultiH1 = $tabSheetIds['Multiple H1'] ?? null;
                if ($sheetIdMultiH1 !== null) {
                    $multiRows = [];
                    foreach ($geminiResult['multiple_h1'] as $idx => $item) {
                        $h1List = is_array($item['h1_contents']) ? implode(' | ', $item['h1_contents']) : ($item['h1_contents'] ?? '');
                        $multiRows[] = [
                            $idx + 1,
                            $item['url']           ?? '',
                            'Multiple H1',
                            $item['total_h1_tags'] ?? '',
                            $h1List,
                            '',   // Existing H2 – left for the team
                            '',   // Comments
                            '',   // Status
                        ];
                    }

                    $sheetsService->spreadsheets_values->update(
                        $spreadsheetId,
                        'Multiple H1!A2',
                        new ValueRange(['values' => $multiRows]),
                        ['valueInputOption' => 'USER_ENTERED']
                    );

                    $multiRequests = [];
                    foreach ($multiRows as $rowIdx => $_) {
                        $ri = $rowIdx + 1;
                        $multiRequests[] = new SheetsRequest([
                            'repeatCell' => [
                                'range'  => ['sheetId' => $sheetIdMultiH1, 'startRowIndex' => $ri, 'endRowIndex' => $ri + 1, 'startColumnIndex' => 0, 'endColumnIndex' => 8],
                                'cell'   => ['userEnteredFormat' => ['backgroundColor' => ['red' => 1.0, 'green' => 0.851, 'blue' => 0.4]]],
                                'fields' => 'userEnteredFormat.backgroundColor',
                            ],
                        ]);
                    }
                    $multiRequests[] = new SheetsRequest(['autoResizeDimensions' => ['dimensions' => ['sheetId' => $sheetIdMultiH1, 'dimension' => 'COLUMNS', 'startIndex' => 0, 'endIndex' => 8]]]);
                    $sheetsService->spreadsheets->batchUpdate($spreadsheetId, new BatchUpdateSpreadsheetRequest(['requests' => $multiRequests]));
                }
            }

            // ── Populate "Missing Alt Text" sheet from Gemini result ─────────────────
            if (!empty($geminiResult['missing_alt_text_images'])) {
                $sheetIdAlt = $tabSheetIds['Missing Alt Text'] ?? null;
                if ($sheetIdAlt !== null) {
                    $altRows = [];
                    foreach ($geminiResult['missing_alt_text_images'] as $idx => $item) {
                        $altRows[] = [
                            $idx + 1,
                            $item['page_url']       ?? '',
                            $item['image_src']      ?? '',
                            'Missing Alt Text',
                            '',   // Recommended Alt Text – left for the team
                            '',   // Comments
                            now()->format('Y-m-d'),
                        ];
                    }

                    $sheetsService->spreadsheets_values->update(
                        $spreadsheetId,
                        'Missing Alt Text!A2',
                        new ValueRange(['values' => $altRows]),
                        ['valueInputOption' => 'USER_ENTERED']
                    );

                    $altRequests = [];
                    foreach ($altRows as $rowIdx => $_) {
                        $ri = $rowIdx + 1;
                        $altRequests[] = new SheetsRequest([
                            'repeatCell' => [
                                'range'  => ['sheetId' => $sheetIdAlt, 'startRowIndex' => $ri, 'endRowIndex' => $ri + 1, 'startColumnIndex' => 0, 'endColumnIndex' => 7],
                                'cell'   => ['userEnteredFormat' => ['backgroundColor' => ['red' => 1.0, 'green' => 0.949, 'blue' => 0.8]]],
                                'fields' => 'userEnteredFormat.backgroundColor',
                            ],
                        ]);
                    }
                    $altRequests[] = new SheetsRequest(['autoResizeDimensions' => ['dimensions' => ['sheetId' => $sheetIdAlt, 'dimension' => 'COLUMNS', 'startIndex' => 0, 'endIndex' => 7]]]);
                    $sheetsService->spreadsheets->batchUpdate($spreadsheetId, new BatchUpdateSpreadsheetRequest(['requests' => $altRequests]));
                }
            }

            // ── Populate the "Oversize Images" sheet from PageSpeed Insights ──────────
            $oversizeImages  = $this->fetchOversizeImages(rtrim($domain, '/'));
            $sheetIdOversize = $tabSheetIds['Oversize Images'] ?? null;

            if (!empty($oversizeImages) && $sheetIdOversize !== null) {
                // Build value rows: [S.No., Page URL, Image URL, File Size (KB), Fixed, Recommendation, Comments]
                $imageRows = [];
                foreach ($oversizeImages as $idx => $img) {
                    $imageRows[] = [
                        $idx + 1,
                        $img['pageUrl'],
                        $img['imageUrl'],
                        $img['fileSizeKb'],
                        $img['fixed'],
                        $img['recommendation'],
                        $img['comments'],
                    ];
                }

                // Write data from row 2 onwards (row 1 is the header)
                $sheetsService->spreadsheets_values->update(
                    $spreadsheetId,
                    'Oversize Images!A2',
                    new ValueRange(['values' => $imageRows]),
                    ['valueInputOption' => 'USER_ENTERED']
                );

                // Green highlight every data row + auto-resize columns
                $oversizeRequests = [];
                foreach ($imageRows as $rowIdx => $imageRow) {
                    $sheetRowIndex = $rowIdx + 1; // 0-based; row 0 = header
                    $oversizeRequests[] = new SheetsRequest([
                        'repeatCell' => [
                            'range' => [
                                'sheetId'          => $sheetIdOversize,
                                'startRowIndex'    => $sheetRowIndex,
                                'endRowIndex'      => $sheetRowIndex + 1,
                                'startColumnIndex' => 0,
                                'endColumnIndex'   => 7,
                            ],
                            'cell' => [
                                'userEnteredFormat' => [
                                    'backgroundColor' => [
                                        'red'   => 0.576,   // #93c47d – matches 404 sheet green
                                        'green' => 0.769,
                                        'blue'  => 0.49,
                                    ],
                                ],
                            ],
                            'fields' => 'userEnteredFormat.backgroundColor',
                        ],
                    ]);
                }

                // Auto-resize all 7 columns
                $oversizeRequests[] = new SheetsRequest([
                    'autoResizeDimensions' => [
                        'dimensions' => [
                            'sheetId'    => $sheetIdOversize,
                            'dimension'  => 'COLUMNS',
                            'startIndex' => 0,
                            'endIndex'   => 7,
                        ],
                    ],
                ]);

                if (!empty($oversizeRequests)) {
                    $sheetsService->spreadsheets->batchUpdate(
                        $spreadsheetId,
                        new BatchUpdateSpreadsheetRequest(['requests' => $oversizeRequests])
                    );
                }
            }

            // ── Populate the "Page Speed" sheet ──────────────────────────────────────
            $pageSpeedSheetId = $tabSheetIds['Page Speed'] ?? null;
            if ($pageSpeedSheetId !== null) {
                $this->populatePageSpeedSheet(
                    $sheetsService,
                    $spreadsheetId,
                    $pageSpeedSheetId,
                    rtrim($domain, '/')
                );
            }

            // ── Style the Dashboard tab ───────────────────────────────────────────────
            $dashboardSheetId = $tabSheetIds['Dashboard'] ?? null;
            if ($dashboardSheetId) {
                // Clear existing Dashboard content first
                $sheetsService->spreadsheets_values->clear(
                    $spreadsheetId,
                    'Dashboard!A:C',
                    new \Google\Service\Sheets\ClearValuesRequest()
                );
                
                $this->styleDashboard($sheetsService, $spreadsheetId, $dashboardSheetId);
            }

            // ── Build Dashboard row data ──────────────────────────────────────────────
            $spreadsheetUrl = 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId . '/edit';
            $rows = $this->buildDashboardRows(
                $sheetDefinitions,
                $tabSheetIds,
                $spreadsheetId,
                $spreadsheetUrl
            );

            $valueData = [['Dashboard'], ['S. No', 'Error', 'Doc File']];
            foreach ($rows as $row) {
                $valueData[] = [
                    $row['sno'],
                    $row['name'],
                    $row['url'] ?? ($row['note'] ?? ''),
                ];
            }

            $sheetsService->spreadsheets_values->update(
                $spreadsheetId,
                'Dashboard!A1',
                new ValueRange(['values' => $valueData]),
                ['valueInputOption' => 'USER_ENTERED']
            );

            return response()->json([
                'success'         => true,
                'spreadsheet_url' => $spreadsheetUrl,
                'rows'            => $rows,
            ]);

        } catch (Exception $e) {
            Log::error('Reporting Sheet Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update spreadsheet: ' . $e->getMessage(),
            ], 500);
        }
    }

/**
 * Helper method to convert column index to letter (e.g., 0 -> A, 25 -> Z, 26 -> AA)
 */
private function getColumnLetter($index)
{
    $letter = '';
    while ($index >= 0) {
        $letter = chr($index % 26 + 65) . $letter;
        $index = floor($index / 26) - 1;
    }
    return $letter;
}

    // ── PRIVATE HELPERS ───────────────────────────────────────────────────────────

    /**
     * Returns a repeatCell SheetsRequest that styles row 0 of a given sheet
     * with the dark-blue header style.
     */
    private function makeHeaderStyleRequest(int $sheetId, int $numCols): SheetsRequest
    {
        $darkBlue = ['red' => 0.118, 'green' => 0.227, 'blue' => 0.373];
        $white    = ['red' => 1,     'green' => 1,     'blue' => 1];

        return new SheetsRequest([
            'repeatCell' => [
                'range' => [
                    'sheetId'          => $sheetId,
                    'startRowIndex'    => 0,
                    'endRowIndex'      => 1,
                    'startColumnIndex' => 0,
                    'endColumnIndex'   => $numCols,
                ],
                'cell' => [
                    'userEnteredFormat' => [
                        'backgroundColor'     => $darkBlue,
                        'horizontalAlignment' => 'CENTER',
                        'textFormat'          => [
                            'bold'            => true,
                            'foregroundColor' => $white,
                        ],
                    ],
                ],
                'fields' => 'userEnteredFormat(backgroundColor,horizontalAlignment,textFormat)',
            ],
        ]);
    }

    /**
     * Build the rows shown in the Dashboard (both in the sheet and on-screen).
     */
    private function buildDashboardRows(
        array  $sheetDefinitions,
        array  $tabSheetIds,
        string $spreadsheetId,
        string $spreadsheetUrl
    ): array {
        $rows = [];
        $sno  = 1;

        foreach ($sheetDefinitions as $def) {
            $name = $def['name'];

            if (isset($def['note'])) {
                $rows[] = ['sno' => $sno, 'name' => $name, 'note' => $def['note']];

            } elseif (isset($def['docType']) && $def['docType'] === 'document') {
                // Audit — links back to the spreadsheet itself as a placeholder.
                // Swap $spreadsheetUrl for a real Google Doc URL if you have one.
                $rows[] = ['sno' => $sno, 'name' => $name, 'url' => $spreadsheetUrl];

            } else {
                $gid    = $tabSheetIds[$name] ?? 0;
                $url    = $spreadsheetUrl . '#gid=' . $gid;
                $rows[] = ['sno' => $sno, 'name' => $name, 'url' => $url];
            }

            $sno++;
        }

        return $rows;
    }

    /**
     * Apply header styling to the Dashboard tab.
     */
    private function styleDashboard(Sheets $service, string $spreadsheetId, int $dashboardSheetId): void
    {
        $darkBlue = ['red' => 0.118, 'green' => 0.227, 'blue' => 0.373];
        $white    = ['red' => 1,     'green' => 1,     'blue' => 1];

        $requests = [
            // Merge A1:C1 for the "Dashboard" title
            new SheetsRequest([
                'mergeCells' => [
                    'range' => [
                        'sheetId'          => $dashboardSheetId,
                        'startRowIndex'    => 0,
                        'endRowIndex'      => 1,
                        'startColumnIndex' => 0,
                        'endColumnIndex'   => 3,
                    ],
                    'mergeType' => 'MERGE_ALL',
                ],
            ]),
            // Style row 1 — title
            new SheetsRequest([
                'repeatCell' => [
                    'range' => [
                        'sheetId'          => $dashboardSheetId,
                        'startRowIndex'    => 0,
                        'endRowIndex'      => 1,
                        'startColumnIndex' => 0,
                        'endColumnIndex'   => 3,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'backgroundColor'     => $darkBlue,
                            'horizontalAlignment' => 'CENTER',
                            'textFormat'          => ['bold' => true, 'fontSize' => 12, 'foregroundColor' => $white],
                        ],
                    ],
                    'fields' => 'userEnteredFormat(backgroundColor,horizontalAlignment,textFormat)',
                ],
            ]),
            // Style row 2 — column headers
            new SheetsRequest([
                'repeatCell' => [
                    'range' => [
                        'sheetId'          => $dashboardSheetId,
                        'startRowIndex'    => 1,
                        'endRowIndex'      => 2,
                        'startColumnIndex' => 0,
                        'endColumnIndex'   => 3,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'backgroundColor'     => $darkBlue,
                            'horizontalAlignment' => 'CENTER',
                            'textFormat'          => ['bold' => true, 'foregroundColor' => $white],
                        ],
                    ],
                    'fields' => 'userEnteredFormat(backgroundColor,horizontalAlignment,textFormat)',
                ],
            ]),
            // Auto-resize A, B, C
            new SheetsRequest([
                'autoResizeDimensions' => [
                    'dimensions' => [
                        'sheetId'    => $dashboardSheetId,
                        'dimension'  => 'COLUMNS',
                        'startIndex' => 0,
                        'endIndex'   => 3,
                    ],
                ],
            ]),
        ];

        $service->spreadsheets->batchUpdate(
            $spreadsheetId,
            new BatchUpdateSpreadsheetRequest(['requests' => $requests])
        );
    }

    /**
     * Fetch broken / non-crawlable links from the PageSpeed Insights API
     * for the given domain URL.
     *
     * PageSpeed exposes two useful audits:
     *   • "link-text"         – anchors with non-descriptive text
     *   • "crawlable-anchors" – anchors that Googlebot cannot follow
     *
     * For 404-style broken links we use the "links-crawlable" / HTTP-error
     * data surfaced inside the "resource-summary" and "network-requests" audits
     * (category=SEO, strategy=mobile is fine for link discovery).
     *
     * Returns an array of rows ready to be written into the "404" sheet:
     *   [linkFrom, url, statusCode, linkOnText, comments]
     */
    private function fetch404BrokenLinks(string $domain): array
    {
        $apiKey = env('PAGESPEED_API_KEY');

        if (empty($apiKey)) {
            Log::warning('fetch404BrokenLinks: PAGESPEED_API_KEY is not set.');
            return [];
        }

        // We request ALL categories so we can read every audit block.
        $queryParams = [
            'url'      => $domain,
            'key'      => $apiKey,
            'strategy' => 'mobile',
        ];
        $queryString = http_build_query($queryParams);
        $queryString .= '&category=seo&category=best-practices&category=performance';
        $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?'.$queryString;
        try {
            $response = Http::timeout(90)->get($apiUrl);
        } catch (\Exception $e) {
            Log::error('fetch404BrokenLinks HTTP error: ' . $e->getMessage());
            return [];
        }
        // $response = Http::timeout(90)->get($apiUrl);
        

        if ($response->failed()) {
            Log::error('fetch404BrokenLinks: PageSpeed API returned ' . $response->status());
            return [];
        }

        $data   = $response->json();
        $audits = $data['lighthouseResult']['audits'] ?? [];
        $rows   = [];

        // ── 1. network-requests audit ─────────────────────────────────────────────
        // Contains every resource the page loaded, including status codes.
        // We filter for 4xx / 5xx responses that came from the same origin (broken
        // internal links) or any anchor target the crawler detected.
        $networkItems = $audits['network-requests']['details']['items'] ?? [];

        foreach ($networkItems as $item) {
            $statusCode = (int) ($item['statusCode'] ?? 0);
            $url        = $item['url'] ?? '';
            $resourceType = strtolower($item['resourceType'] ?? '');

            // Only flag HTML/document resources with error status codes
            if (
                $statusCode >= 400
                && $statusCode < 600
                && in_array($resourceType, ['document', 'xhr', 'fetch', ''])
                && !empty($url)
            ) {
                $rows[] = [
                    'linkFrom'   => $domain,
                    'url'        => $url,
                    'statusCode' => $statusCode,
                    'linkOnText' => '-',
                    'comments'   => $statusCode >= 500
                                        ? 'Server error – investigate hosting'
                                        : 'Remove ' . $statusCode . ' URL from Anchor / Page',
                ];
            }
        }

        // ── 2. crawlable-anchors audit ────────────────────────────────────────────
        // Lists anchors that Googlebot cannot crawl (javascript:void, empty href, etc.)
        $crawlableItems = $audits['crawlable-anchors']['details']['items'] ?? [];

        foreach ($crawlableItems as $item) {
            $node       = $item['node'] ?? [];
            $href       = $node['snippet'] ?? ($item['href'] ?? '');
            $nodeLabel  = $node['nodeLabel'] ?? '-';

            // Extract the href value from the snippet if possible
            if (preg_match('/href=["\']([^"\']*)["\']/', $href, $m)) {
                $href = $m[1];
            }

            if (empty($href)) {
                continue;
            }

            // Avoid duplicates already caught by network-requests
            $alreadyAdded = collect($rows)->pluck('url')->contains($href);
            if (!$alreadyAdded) {
                $rows[] = [
                    'linkFrom'   => $domain,
                    'url'        => $href,
                    'statusCode' => 'Non-crawlable',
                    'linkOnText' => $nodeLabel,
                    'comments'   => 'Non-crawlable anchor – fix href attribute',
                ];
            }
        }

        // ── 3. tap "links-crawlable" / "hreflang" / "is-crawlable" audits ─────────
        // Some PSI versions surface broken links under "tap-targets" or
        // "link-text"; grab anything that looks like a URL error.
        $linkTextItems = $audits['link-text']['details']['items'] ?? [];

        foreach ($linkTextItems as $item) {
            $node      = $item['node'] ?? [];
            $href      = $node['snippet'] ?? '';
            $nodeLabel = $node['nodeLabel'] ?? '-';

            if (preg_match('/href=["\']([^"\']*)["\']/', $href, $m)) {
                $href = $m[1];
            }

            if (empty($href)) {
                continue;
            }

            $alreadyAdded = collect($rows)->pluck('url')->contains($href);
            if (!$alreadyAdded) {
                $rows[] = [
                    'linkFrom'   => $domain,
                    'url'        => $href,
                    'statusCode' => 'Non-descriptive',
                    'linkOnText' => $nodeLabel,
                    'comments'   => 'Link text is non-descriptive – update anchor text',
                ];
            }
        }

        return $rows;
    }

    /**
     * Fetch oversize images from the PageSpeed Insights API.
     *
     * Mines three audits:
     *   • uses-optimized-images   – images that should be compressed
     *   • uses-responsive-images  – images served larger than needed
     *   • network-requests        – to get the actual transfer size per URL
     *
     * Returns rows matching the "Oversize Images" sheet headers:
     *   [pageUrl, imageUrl, fileSizeKb, fixed, recommendation, comments]
     *
     * Threshold: images whose transfer size exceeds $thresholdKb (default 100 KB).
     */
    private function fetchOversizeImages(string $domain, int $thresholdKb = 100): array
    {
        $apiKey = env('PAGESPEED_API_KEY');

        if (empty($apiKey)) {
            Log::warning('fetchOversizeImages: PAGESPEED_API_KEY is not set.');
            return [];
        }

        $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?' . http_build_query([
            'url'      => $domain,
            'key'      => $apiKey,
            'strategy' => 'mobile',
            'category' => 'performance',
        ]);

        try {
            $response = Http::timeout(90)->get($apiUrl);
        } catch (\Exception $e) {
            Log::error('fetchOversizeImages HTTP error: ' . $e->getMessage());
            return [];
        }

        if ($response->failed()) {
            Log::error('fetchOversizeImages: PageSpeed API returned ' . $response->status());
            return [];
        }

        $data   = $response->json();
        $audits = $data['lighthouseResult']['audits'] ?? [];

        // ── Build a size map from network-requests: url => transferSize (bytes) ────
        $sizeMap = [];
        foreach ($audits['network-requests']['details']['items'] ?? [] as $item) {
            $url          = $item['url']          ?? '';
            $transferSize = $item['transferSize'] ?? ($item['resourceSize'] ?? 0);
            $mimeType     = strtolower($item['mimeType'] ?? '');

            if ($url && str_starts_with($mimeType, 'image/')) {
                $sizeMap[$url] = (int) $transferSize;
            }
        }

        // ── Collect flagged image URLs from the two optimisation audits ───────────
        $flaggedImages = [];   // url => ['wastedBytes' => int, 'recommendation' => string]

        $optimisedItems = $audits['uses-optimized-images']['details']['items'] ?? [];
        foreach ($optimisedItems as $item) {
            $url         = $item['url']          ?? '';
            $wastedBytes = $item['wastedBytes']  ?? 0;
            if ($url) {
                $flaggedImages[$url] = [
                    'wastedBytes' => $wastedBytes,
                    'recommendation'        => 'Compress image to reduce file size',
                ];
            }
        }

        $responsiveItems = $audits['uses-responsive-images']['details']['items'] ?? [];
        foreach ($responsiveItems as $item) {
            $url         = $item['url']         ?? '';
            $wastedBytes = $item['wastedBytes'] ?? 0;
            if ($url && !isset($flaggedImages[$url])) {
                $flaggedImages[$url] = [
                    'wastedBytes' => $wastedBytes,
                    'recommendation'        => 'Serve image at display dimensions to reduce size',
                ];
            }
        }

        // ── Also sweep network-requests for any image exceeding the threshold ─────
        foreach ($sizeMap as $url => $bytes) {
            $kb = $bytes / 1024;
            if ($kb > $thresholdKb && !isset($flaggedImages[$url])) {
                $flaggedImages[$url] = [
                    'wastedBytes' => 0,
                    'recommendation'        => 'Over ' . $thresholdKb . ' KB – compress or lazy-load',
                ];
            }
        }

        // ── Build final rows, only include images that exceed the threshold ────────
        $rows = [];
        foreach ($flaggedImages as $imageUrl => $meta) {
            $transferBytes = $sizeMap[$imageUrl] ?? 0;

            // Fall back to wastedBytes estimate if network size is missing
            $sizeBytes = $transferBytes > 0 ? $transferBytes : $meta['wastedBytes'];
            $sizeKb    = round($sizeBytes / 1024, 2);

            // Skip images under the threshold (only in the PSI audit, not network)
            if ($sizeKb < $thresholdKb && $transferBytes === 0) {
                continue;
            }

            $rows[] = [
                'pageUrl'    => $domain,
                'imageUrl'   => $imageUrl,
                'fileSizeKb' => $sizeKb > 0 ? $sizeKb . ' KB' : '-',
                'fixed'      => $thresholdKb . 'KB',           // target threshold column
                'recommendation' => $meta['recommendation'],                             // not available from PSI
                'comments'      => "",
            ];
        }

        // Sort largest-first
        usort($rows, fn($a, $b) => (float) $b['fileSizeKb'] <=> (float) $a['fileSizeKb']);

        return $rows;
    }

    /**
     * Fetch PageSpeed Insights data (scores, metrics, full-page screenshot)
     * for both desktop and mobile strategies.
     *
     * Returns:
     *   [
     *     'desktop' => [ 'scores' => [...], 'metrics' => [...], 'screenshotBase64' => '...', 'screenshotMime' => '...' ],
     *     'mobile'  => [ ... ],
     *   ]
     */
    private function fetchPageSpeedData(string $domain): array
    {
        $apiKey  = env('PAGESPEED_API_KEY');
        $results = [];
        $categories = ['performance', 'accessibility', 'best-practices', 'seo'];

        foreach (['mobile', 'desktop'] as $strategy) {
            $queryParams = [
                'url'      => $domain,
                'key'      => $apiKey,
                'strategy' => $strategy,
            ];

            // Manually append categories (important)
            $queryString = http_build_query($queryParams);

            foreach ($categories as $category) {
                $queryString .= '&category=' . urlencode($category);
            }

            $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?' . $queryString;
            
            // Alternative: Manually build if the above doesn't work
            // $apiUrl = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?" .
            //           "url=" . urlencode($domain) .
            //           "&key=" . $apiKey .
            //           "&strategy=" . $strategy .
            //           "&category=performance" .
            //           "&category=accessibility" .
            //           "&category=best-practices" .
            //           "&category=seo";

            try {
                $response = Http::timeout(120)->get($apiUrl);
            } catch (\Exception $e) {
                Log::error("fetchPageSpeedData [{$strategy}] HTTP error: " . $e->getMessage());
                continue;
            }

            if ($response->failed()) {
                Log::error("fetchPageSpeedData [{$strategy}] PSI returned " . $response->status());
                continue;
            }

            $data       = $response->json();
            $lhr        = $data['lighthouseResult']  ?? [];
            $audits     = $lhr['audits']             ?? [];
            $categories = $lhr['categories']         ?? [];

            // ── Category scores ────────────────────────────────────────────────────
            $score = fn(string $cat) => isset($categories[$cat]['score'])
                ? (int) round($categories[$cat]['score'] * 100)
                : null;

            $scores = [
                'performance'    => $score('performance'),
                'accessibility'  => $score('accessibility'),
                'best-practices' => $score('best-practices'),
                'seo'            => $score('seo'),
            ];

            // ── Core metrics ───────────────────────────────────────────────────────
            $display = fn(string $key) => $audits[$key]['displayValue'] ?? '—';

            $metrics = [
                'First Contentful Paint'    => $display('first-contentful-paint'),
                'Largest Contentful Paint'  => $display('largest-contentful-paint'),
                'Total Blocking Time'       => $display('total-blocking-time'),
                'Cumulative Layout Shift'   => $display('cumulative-layout-shift'),
                'Speed Index'               => $display('speed-index'),
                'Time to Interactive'       => $display('interactive'),
            ];

            // ── Full-page screenshot ────────────────────────────────────────────────
            // PSI returns a full-page screenshot in the 'full-page-screenshot' audit
            // as a base64 data URI. Fall back to the final-screenshot if unavailable.
            $screenshotBase64 = null;
            $screenshotMime   = 'image/jpeg';

            $fpsAudit = $audits['fullPageScreenshot'] ?? [];
            if (!empty($fpsAudit['screenshot']['data'])) {
                $raw              = $fpsAudit['screenshot']['data'];
                // Strip "data:image/...;base64," prefix if present
                if (str_contains($raw, ';base64,')) {
                    [$mimePrefix, $b64] = explode(';base64,', $raw, 2);
                    $screenshotMime   = str_replace('data:', '', $mimePrefix);
                    $screenshotBase64 = $b64;
                } else {
                    $screenshotBase64 = $raw;
                }
            }

            // Fallback: final-screenshot (smaller, above-the-fold only)
            if (empty($screenshotBase64)) {
                $fsAudit = $audits['final-screenshot'] ?? [];
                $raw     = $fsAudit['details']['data'] ?? '';
                if (!empty($raw)) {
                    if (str_contains($raw, ';base64,')) {
                        [$mimePrefix, $b64] = explode(';base64,', $raw, 2);
                        $screenshotMime   = str_replace('data:', '', $mimePrefix);
                        $screenshotBase64 = $b64;
                    } else {
                        $screenshotBase64 = $raw;
                    }
                }
            }

            $results[$strategy] = [
                'scores'  => $scores,
                'metrics' => $metrics,
                // screenshotBase64 / screenshotMime removed — we now link to the
                // PageSpeed Insights report URL instead of uploading to Drive.
            ];
        }

        return $results;
    }

    /**
     * Upload a base64 image to Google Drive (in a hidden app folder) and return
     * a publicly accessible direct-download URL that =IMAGE() can consume.
     *
     * The file is created with "anyone with link can view" permission so that
     * Google Sheets can render it via =IMAGE().
     */
    private function uploadScreenshotToDrive(
        GoogleClient $googleClient,
        string       $base64Data,
        string       $mimeType,
        string       $filename
    ): ?string {
        try {
            $driveService = new Drive($googleClient);

            // Decode base64 → binary
            $binaryData = base64_decode($base64Data);
            if ($binaryData === false) {
                Log::error("uploadScreenshotToDrive: base64_decode failed for [{$filename}]");
                return null;
            }

            // Create a temp file
            $tmpPath = sys_get_temp_dir() . '/' . $filename;
            file_put_contents($tmpPath, $binaryData);

            // Upload to Drive
            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name'     => $filename,
                'mimeType' => $mimeType,
            ]);

            $uploadedFile = $driveService->files->create(
                $fileMetadata,
                [
                    'data'       => $binaryData,
                    'mimeType'   => $mimeType,
                    'uploadType' => 'multipart',
                    'fields'     => 'id',
                ]
            );

            $fileId = $uploadedFile->getId();

            // Make it publicly readable
            $permission = new \Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $driveService->permissions->create($fileId, $permission);

            // Clean up temp file
            @unlink($tmpPath);

            // Return a direct image URL (works with =IMAGE())
            return "https://drive.google.com/uc?export=view&id={$fileId}";

        } catch (\Exception $e) {
            Log::error("uploadScreenshotToDrive error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Populate the "Page Speed" sheet with:
     *   • A "Page Speed" title row (dark-blue, merged)
     *   • For each strategy (Desktop then Mobile):
     *       – Section label row  (bold, light-blue background)
     *       – Score row          (Performance | Accessibility | Best Practices | SEO)
     *       – Metric rows        (FCP, LCP, TBT, CLS, SI, TTI)
     *       – Screenshot row     (=IMAGE() formula spanning multiple rows via row height)
     *
     * Layout mirrors the screenshot in the brief:
     *   Row 1  : "Page Speed"  (merged A1:F1, dark-blue)
     *   Row 2  : "Desktop"     (merged A2:F2, light-blue)
     *   Row 3  : Score headers
     *   Row 4  : Score values
     *   Row 5  : Metric labels  (left pair)  |  Metric labels  (right pair)
     *   Row 6  : Metric values  (left pair)  |  Metric values  (right pair)
     *   Row 7  : …more metrics
     *   Row 8  : Screenshot     (=IMAGE in A8, row height set to ~400 px)
     *   Row 9  : (blank spacer)
     *   Row 10 : "Mobile"      … (same pattern)
     */
    private function populatePageSpeedSheet(
        Sheets       $sheetsService,
        string       $spreadsheetId,
        int          $pageSpeedSheetId,
        string       $domain
    ): void {
        $psData = $this->fetchPageSpeedData(rtrim($domain, '/'));

        if (empty($psData)) {
            Log::warning('populatePageSpeedSheet: no PSI data returned.');
            return;
        }

        // ── Colour palette ─────────────────────────────────────────────────────────
        $darkBlue  = ['red' => 0.118, 'green' => 0.227, 'blue' => 0.373];
        $white     = ['red' => 1.0,   'green' => 1.0,   'blue' => 1.0];
        $lightBlue = ['red' => 0.812, 'green' => 0.886, 'blue' => 0.953];   // #CFE2F3
        $darkText  = ['red' => 0.2,   'green' => 0.2,   'blue' => 0.2];
        $green     = ['red' => 0.576, 'green' => 0.769, 'blue' => 0.49];

        // ── Clear the sheet first ──────────────────────────────────────────────────
        $sheetsService->spreadsheets_values->clear(
            $spreadsheetId,
            'Page Speed!A1:Z200',
            new \Google\Service\Sheets\ClearValuesRequest()
        );

        // ── Helper: score colour (red/orange/green) ────────────────────────────────
        $scoreColour = function (int $score): array {
            if ($score >= 90) return ['red' => 0.055, 'green' => 0.616, 'blue' => 0.408]; // green
            if ($score >= 50) return ['red' => 1.0,   'green' => 0.718, 'blue' => 0.22];  // orange
            return                   ['red' => 0.918, 'green' => 0.267, 'blue' => 0.208]; // red
        };

        $valueData    = [];   // rows to write via spreadsheets_values->update
        $formatReqs   = [];   // batchUpdate formatting requests
        $currentRow   = 0;   // 0-based row index

        // ── Row 0: "Page Speed" title (merged A:F) ─────────────────────────────────
        $valueData[] = ['Page Speed', '', '', '', '', ''];

        $formatReqs[] = new SheetsRequest(['mergeCells' => [
            'range'     => ['sheetId' => $pageSpeedSheetId, 'startRowIndex' => 0, 'endRowIndex' => 1, 'startColumnIndex' => 0, 'endColumnIndex' => 6],
            'mergeType' => 'MERGE_ALL',
        ]]);
        $formatReqs[] = new SheetsRequest(['repeatCell' => [
            'range'  => ['sheetId' => $pageSpeedSheetId, 'startRowIndex' => 0, 'endRowIndex' => 1, 'startColumnIndex' => 0, 'endColumnIndex' => 6],
            'cell'   => ['userEnteredFormat' => ['backgroundColor' => $darkBlue, 'horizontalAlignment' => 'LEFT', 'textFormat' => ['bold' => true, 'fontSize' => 12, 'foregroundColor' => $white]]],
            'fields' => 'userEnteredFormat(backgroundColor,horizontalAlignment,textFormat)',
        ]]);
        $currentRow = 1;

        // ── Loop: Desktop then Mobile ──────────────────────────────────────────────
        foreach (['desktop' => 'Desktop', 'mobile' => 'Mobile'] as $strategy => $label) {
            $data = $psData[$strategy] ?? null;
            if (!$data) continue;

            $scores  = $data['scores'];
            $metrics = $data['metrics'];

            // ── Section label row (e.g. "Desktop") ────────────────────────────────
            $valueData[] = [$label, '', '', '', '', ''];

            $formatReqs[] = new SheetsRequest(['mergeCells' => [
                'range'     => ['sheetId' => $pageSpeedSheetId, 'startRowIndex' => $currentRow, 'endRowIndex' => $currentRow + 1, 'startColumnIndex' => 0, 'endColumnIndex' => 6],
                'mergeType' => 'MERGE_ALL',
            ]]);
            $formatReqs[] = new SheetsRequest(['repeatCell' => [
                'range'  => ['sheetId' => $pageSpeedSheetId, 'startRowIndex' => $currentRow, 'endRowIndex' => $currentRow + 1, 'startColumnIndex' => 0, 'endColumnIndex' => 6],
                'cell'   => ['userEnteredFormat' => ['backgroundColor' => $lightBlue, 'horizontalAlignment' => 'LEFT', 'textFormat' => ['bold' => true, 'fontSize' => 11, 'foregroundColor' => $darkText]]],
                'fields' => 'userEnteredFormat(backgroundColor,horizontalAlignment,textFormat)',
            ]]);
            $currentRow++;

            // ── Score header row ───────────────────────────────────────────────────
            $valueData[] = ['Performance', '', 'Accessibility', '', 'Best Practices', 'SEO'];
            $formatReqs[] = new SheetsRequest(['repeatCell' => [
                'range'  => ['sheetId' => $pageSpeedSheetId, 'startRowIndex' => $currentRow, 'endRowIndex' => $currentRow + 1, 'startColumnIndex' => 0, 'endColumnIndex' => 6],
                'cell'   => ['userEnteredFormat' => ['horizontalAlignment' => 'CENTER', 'textFormat' => ['bold' => true]]],
                'fields' => 'userEnteredFormat(horizontalAlignment,textFormat)',
            ]]);
            $currentRow++;

            // ── Score value row ────────────────────────────────────────────────────
            $perfScore  = $scores['performance']    ?? 0;
            $a11yScore  = $scores['accessibility']  ?? 0;
            $bpScore    = $scores['best-practices'] ?? 0;
            $seoScore   = $scores['seo']            ?? 0;

            $valueData[] = [$perfScore, '', $a11yScore, '', $bpScore, $seoScore];

            // Colour each score cell individually
            foreach ([
                [0, $perfScore],
                [2, $a11yScore],
                [4, $bpScore],
                [5, $seoScore],
            ] as [$col, $sc]) {
                $formatReqs[] = new SheetsRequest(['repeatCell' => [
                    'range'  => ['sheetId' => $pageSpeedSheetId, 'startRowIndex' => $currentRow, 'endRowIndex' => $currentRow + 1, 'startColumnIndex' => $col, 'endColumnIndex' => $col + 1],
                    'cell'   => ['userEnteredFormat' => ['horizontalAlignment' => 'CENTER', 'textFormat' => ['bold' => true, 'fontSize' => 14, 'foregroundColor' => $scoreColour($sc)]]],
                    'fields' => 'userEnteredFormat(horizontalAlignment,textFormat)',
                ]]);
            }
            $currentRow++;

            // ── Metrics: two columns of label+value pairs ──────────────────────────
            // Layout: col A = metric name, col B = value | col D = metric name, col E = value
            $metricList    = array_keys($metrics);
            $metricValues  = array_values($metrics);
            $metricCount   = count($metricList);

            for ($i = 0; $i < $metricCount; $i += 2) {
                $leftLabel  = $metricList[$i]      ?? '';
                $leftVal    = $metricValues[$i]    ?? '';
                $rightLabel = $metricList[$i + 1]  ?? '';
                $rightVal   = $metricValues[$i + 1] ?? '';

                // Label row
                $valueData[] = [$leftLabel, '', '', $rightLabel, '', ''];
                $formatReqs[] = new SheetsRequest(['repeatCell' => [
                    'range'  => ['sheetId' => $pageSpeedSheetId, 'startRowIndex' => $currentRow, 'endRowIndex' => $currentRow + 1, 'startColumnIndex' => 0, 'endColumnIndex' => 6],
                    'cell'   => ['userEnteredFormat' => ['textFormat' => ['bold' => false, 'fontSize' => 9, 'foregroundColor' => $darkText]]],
                    'fields' => 'userEnteredFormat.textFormat',
                ]]);
                $currentRow++;

                // Value row
                $valueData[] = [$leftVal, '', '', $rightVal, '', ''];
                // Colour values based on score hint (red for slow metrics)
                $formatReqs[] = new SheetsRequest(['repeatCell' => [
                    'range'  => ['sheetId' => $pageSpeedSheetId, 'startRowIndex' => $currentRow, 'endRowIndex' => $currentRow + 1, 'startColumnIndex' => 0, 'endColumnIndex' => 6],
                    'cell'   => ['userEnteredFormat' => ['textFormat' => ['bold' => true, 'fontSize' => 11]]],
                    'fields' => 'userEnteredFormat.textFormat',
                ]]);
                $currentRow++;
            }

            // ── Screenshot URL row ─────────────────────────────────────────────────
            // Instead of uploading the base64 image to Drive, we write the
            // PageSpeed Insights report URL so the team can view the screenshot
            // directly in their browser.
            $psReportUrl = 'https://pagespeed.web.dev/report?url=' . urlencode($domain) . '&form_factor=' . $strategy;

            $valueData[] = ['Screenshot URL:', $psReportUrl, '', '', '', ''];

            // Style the label cell (bold) and make the URL cell a clickable hyperlink
            $formatReqs[] = new SheetsRequest(['repeatCell' => [
                'range'  => ['sheetId' => $pageSpeedSheetId, 'startRowIndex' => $currentRow, 'endRowIndex' => $currentRow + 1, 'startColumnIndex' => 0, 'endColumnIndex' => 1],
                'cell'   => ['userEnteredFormat' => ['textFormat' => ['bold' => true]]],
                'fields' => 'userEnteredFormat.textFormat',
            ]]);
            $currentRow++;

            // ── Blank spacer row between strategies ────────────────────────────────
            $valueData[] = ['', '', '', '', '', ''];
            $currentRow++;
        }

        // ── Write all text/formula values ──────────────────────────────────────────
        $sheetsService->spreadsheets_values->update(
            $spreadsheetId,
            'Page Speed!A1',
            new ValueRange(['values' => $valueData]),
            ['valueInputOption' => 'USER_ENTERED']
        );

        // ── Set a fixed column width so the screenshot fits nicely ────────────────
        $formatReqs[] = new SheetsRequest(['updateDimensionProperties' => [
            'range'      => ['sheetId' => $pageSpeedSheetId, 'dimension' => 'COLUMNS', 'startIndex' => 0, 'endIndex' => 6],
            'properties' => ['pixelSize' => 180],
            'fields'     => 'pixelSize',
        ]]);

        // ── Apply all formatting in one batch ──────────────────────────────────────
        if (!empty($formatReqs)) {
            $sheetsService->spreadsheets->batchUpdate(
                $spreadsheetId,
                new BatchUpdateSpreadsheetRequest(['requests' => $formatReqs])
            );
        }
    }

    /**
     * Obtain a short-lived Google OAuth2 access token from the service-account key.
     */
    private function getGoogleAccessToken(): string
    {
        $keyFilePath = storage_path('app/service-account-key.json');

        if (!file_exists($keyFilePath)) {
            throw new Exception("Service account key file not found: {$keyFilePath}");
        }

        $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
        $creds  = new \Google\Auth\Credentials\ServiceAccountCredentials($scopes, $keyFilePath);
        $token  = $creds->fetchAuthToken();

        if (empty($token['access_token'])) {
            throw new Exception('Could not obtain Google access token for Gemini.');
        }

        return $token['access_token'];
    }

    /**
     * Send the sitemap XML content to Gemini and get back a JSON SEO audit.
     *
     * @param  string $domain      The client's main domain (e.g. https://example.com/)
     * @param  string $xmlContents Raw XML content from one or more sitemap files
     * @return string              Raw JSON string from Gemini
     */
    private function analyzeWithGeminiSeo(string $domain, string $xmlContents): string
    {
        $projectId  = env('GCP_PROJECT_ID', 'composed-arch-472508-u2');
        $location   = env('GCP_LOCATION',   'us-central1');
        $modelId    = env('GEMINI_MODEL_ID', 'gemini-2.5-flash');
        $endpoint   = "https://{$location}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$location}/publishers/google/models/{$modelId}:generateContent";

        $accessToken = $this->getGoogleAccessToken();

        $prompt = <<<PROMPT
Role: You are an advanced Technical SEO Auditor AI specialized in deep-page element extraction.

Input Data:
1. Main Domain: {$domain}
2. Source: Multiple sitemap extracted Urls (provided below).

Operational Constraints:
- Source Restriction: Use ONLY the provided Sitemap XMLs for URL discovery.
- Success Definition: A URL is "Successfully Crawled" if you can access the HTML, even if it contains SEO errors.
- Failure Definition: A URL is "Failed/Skipped" ONLY if there is a 404/500 error, a timeout, or the page is blocked by robots.txt/noindex. SEO issues (like missing Alt parameter on any image or multiple H1s) are NOT crawl failures.

Step 1: URL Extraction & Access
1. Parse all <loc> tags from the provided sitemaps.
2. Deduplicate the list.
3. Access each URL to analyze the live HTML DOM.

Step 2: SEO Analysis Logic
Perform these three specific checks. If a URL has one of these issues, log the data in the specific JSON object and count the URL as "Crawled."
1. Multiple H1 (Quantity Check)
  - Criteria: Any page containing multiple <h1> tags or multiple H1 content.
  - Data to Extract: URL, total count, and the inner text of every H1 found.
2. Duplicate H1 (Content Check)
  - Criteria: A single page where the exact same text string of <h1> tag is used in two or more different heading tags i.e . <h1>, <h2>, <h3>, <h4>, <h5> or <h6>.
  - Data to Extract: The duplicated text, the URL, and how many times that specific string repeated.
3. Missing ALT parameter or text on <img> tags (Attribute Check)
- Criteria: <img> tags where:
  - The alt attribute is missing entirely.
  - The alt attribute is empty (alt="").
  - The alt value is missing entirely.
  - The alt text is a placeholder (e.g., "image", "photo", "img", "screenshot", "picture").
- Exclusion: Ignore SVGs, base64 strings, and 1x1 tracking pixels.
- Data to Extract: Page URL, the image src URL, and the current faulty ALT value.

Final Report Requirements
You must return the output strictly in JSON format. Ensure that URLs with SEO issues are not included in the failed_or_skipped_urls list.

Output Schema:
{
  "extracted_urls_from_sitemaps": [],
  "duplicate_h1": [
    {
      "h1_text": ["The repeated string"],
      "url": "https://example.com/page",
      "occurrences": 2
    }
  ],
  "multiple_h1": [
    {
      "url": "https://example.com/page",
      "total_h1_tags": 3,
      "h1_contents": ["Heading 1", "Heading 2", "Heading 3"]
    }
  ],
  "missing_alt_text_images": [
    {
      "page_url": "https://example.com/page",
      "image_src": "https://example.com/img.jpg",
      "alt_text_found": ""
    }
  ],
  "failed_or_skipped_urls": [
    "List only URLs that could not be reached or timed out"
  ],
  "summary_statistics": {
    "total_urls_discovered_in_sitemap": 0,
    "total_urls_successfully_crawled": 0,
    "total_duplicate_h1_issues": 0,
    "total_multiple_h1_issues": 0,
    "total_missing_alt_issues": 0,
    "total_failed_critical_errors": 0
  }
}

--- SITEMAP XML CONTENT BELOW ---
{$xmlContents}
PROMPT;

        $payload = [
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [['text' => $prompt]],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
            ],
        ];

        $response = Http::timeout(300)
            ->retry(3, 2000)
            ->withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])
            ->post($endpoint, $payload);

        if (!$response->successful()) {
            throw new Exception('Gemini API error: ' . $response->body());
        }

        $data = $response->json();

        if (isset($data['error'])) {
            throw new Exception('Gemini API error: ' . ($data['error']['message'] ?? 'Unknown'));
        }

        $raw = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        // Strip any accidental markdown code fences
        $raw = preg_replace('/^```json\s*/i', '', trim($raw));
        $raw = preg_replace('/\s*```$/', '', $raw);

        return $raw;
    }

    /**
     * Build and return an authenticated Google API client using a service account.
     */
    private function getGoogleClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setAuthConfig(storage_path('app/service-account-key.json'));
        $client->addScope(Sheets::SPREADSHEETS);
        $client->addScope(Drive::DRIVE);

        return $client;
    }

    public function seoDashboard(int $created_by_user_id, int $client_property_id)
    {
        // ── Resolve domain ────────────────────────────────────────────────────
        $clientProperty = Client_propertiesModel::findOrFail($client_property_id);
        $domain         = $clientProperty->domain ?? '';
 
        // ── Raw ranking reports ───────────────────────────────────────────────
        $savedRankingReports = RankingCompetitorReport::where('client_property_id', $client_property_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id'               => $r->id,
                'label'            => \Carbon\Carbon::parse($r->created_at)->format('d M Y, h:i A'),
                'location'         => $r->location,
                'keywords'         => $r->keywords,
                'client_domain'    => $r->client_domain,
                'competitor_option'=> $r->competitor_option,
                'results_json'     => $r->results_json,
                'created_at'       => $r->created_at,
            ]);

    
        // ── Saved Core Web Vitals (same query as coreWebVitals()) ──────────────
        $savedCoreWebVitals = CoreWebVital::where('client_property_id', $client_property_id)
            ->orderByDesc('created_at')
            ->get();
    
        $groupedUrls = [];
 
        $normalise = function (string $url): string {
            $url = trim($url);
            // Ensure scheme so parse_url works
            if (!preg_match('#^https?://#i', $url)) {
                $url = 'https://' . $url;
            }
            $parts = parse_url($url);
            $host  = strtolower($parts['host'] ?? '');
            $host  = preg_replace('/^www\./', '', $host);
            $path  = rtrim($parts['path'] ?? '', '/');
            return $host . $path;
        };
 
        foreach ($savedRankingReports as $r) {
            $raw = $r['client_domain'] ?? '';
            if (!$raw) continue;
            $key = $normalise($raw);
            if (!isset($groupedUrls[$key])) {
                $groupedUrls[$key] = [
                    'display_url' => $r['client_domain'],
                    'ranking'     => [],
                    'cwv'         => [],
                    'latest_at'   => $r['created_at'],
                ];
            }
            $groupedUrls[$key]['ranking'][] = $r;
            // Keep the most recent date for row ordering
            if ($r['created_at'] > $groupedUrls[$key]['latest_at']) {
                $groupedUrls[$key]['latest_at'] = $r['created_at'];
            }
        }
 
        foreach ($savedCoreWebVitals as $c) {
            $raw = $c->url ?? '';
            if (!$raw) continue;
            $key = $normalise($raw);
            if (!isset($groupedUrls[$key])) {
                $groupedUrls[$key] = [
                    'display_url' => $c->url,
                    'ranking'     => [],
                    'cwv'         => [],
                    'latest_at'   => $c->created_at,
                ];
            }
            $groupedUrls[$key]['cwv'][] = $c->toArray();
            if ($c->created_at > $groupedUrls[$key]['latest_at']) {
                $groupedUrls[$key]['latest_at'] = $c->created_at;
            }
        }
 
        // Sort groups newest-first
        uasort($groupedUrls, fn ($a, $b) => $b['latest_at'] <=> $a['latest_at']);
 
        return view('arihant.seo.seo-dashboard', compact(
            'created_by_user_id',
            'client_property_id',
            'domain',
            'savedRankingReports',
            'savedCoreWebVitals',
            'groupedUrls',          // ← new merged structure
        ));
    }

    public function seoSummary(int $created_by_user_id, int $client_property_id, string $urlKey)
    {
        $urlKey = urldecode($urlKey);

        $normalise = function (string $url): string {
            $url = trim($url);
            if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
            $parts = parse_url($url);
            $host  = preg_replace('/^www\./', '', strtolower($parts['host'] ?? ''));
            return $host . rtrim($parts['path'] ?? '', '/');
        };

        $allRanking = RankingCompetitorReport::where('client_property_id', $client_property_id)
            ->orderByDesc('created_at')->get()
            ->filter(fn($r) => $normalise($r->client_domain ?? '') === $urlKey)
            ->map(fn($r) => [
                'id'               => $r->id,
                'location'         => $r->location,
                'keywords'         => $r->keywords,
                'client_domain'    => $r->client_domain,
                'competitor_option'=> $r->competitor_option,
                'results_json'     => $r->results_json,
                'created_at'       => $r->created_at,
            ])->values();

        $allCwv = CoreWebVital::where('client_property_id', $client_property_id)
            ->orderByDesc('created_at')->get()
            ->filter(fn($c) => $normalise($c->url ?? '') === $urlKey)
            ->map(fn($c) => $c->toArray())->values();

        $group = [
            'display_url' => $allRanking->first()['client_domain']
                        ?? $allCwv->first()['url']
                        ?? $urlKey,
            'ranking' => $allRanking->toArray(),
            'cwv'     => $allCwv->toArray(),
        ];

        return view('arihant.seo.seo-summary', compact(
            'created_by_user_id', 'client_property_id', 'urlKey', 'group'
        ));
    }

    public function seopdfSummary(int $created_by_user_id, int $client_property_id, string $urlKey)
    {
        $urlKey = urldecode($urlKey);

        $normalise = function (string $url): string {
            $url = trim($url);
            if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
            $parts = parse_url($url);
            $host  = preg_replace('/^www\./', '', strtolower($parts['host'] ?? ''));
            return $host . rtrim($parts['path'] ?? '', '/');
        };

        $allRanking = RankingCompetitorReport::where('client_property_id', $client_property_id)
            ->orderByDesc('created_at')->get()
            ->filter(fn($r) => $normalise($r->client_domain ?? '') === $urlKey)
            ->map(fn($r) => [
                'id'               => $r->id,
                'location'         => $r->location,
                'keywords'         => $r->keywords,
                'client_domain'    => $r->client_domain,
                'competitor_option'=> $r->competitor_option,
                'results_json'     => $r->results_json,
                'created_at'       => $r->created_at,
            ])->values();

        $allCwv = CoreWebVital::where('client_property_id', $client_property_id)
            ->orderByDesc('created_at')->get()
            ->filter(fn($c) => $normalise($c->url ?? '') === $urlKey)
            ->map(fn($c) => $c->toArray())->values();

        $group = [
            'display_url' => $allRanking->first()['client_domain']
                        ?? $allCwv->first()['url']
                        ?? $urlKey,
            'ranking' => $allRanking->toArray(),
            'cwv'     => $allCwv->toArray(),
        ];

        return view('arihant.seo.seo-pdf-summary', compact(
            'created_by_user_id', 'client_property_id', 'urlKey', 'group'
        ));
    }

    // ============================================================
    //  ADD THIS METHOD TO: SEOAutomationController
    //  Also add this route in web.php or api.php:
    //
    //  Route::post('/seo/broken-links', [SEOAutomationController::class, 'checkBrokenLinks']);
    //
    //  The request body (JSON or form) should contain:
    //    { "domain": "https://example.com" }
    // ============================================================

    /**
     * Check for broken links on a given domain.
     *
     * Strategy:
     *   1. Normalise & validate the domain.
     *   2. Fetch the homepage HTML (no external API key needed).
     *   3. Extract every unique <a href="…"> link.
     *   4. HEAD-request each link and record its HTTP status.
     *   5. Classify results: broken (4xx/5xx/timeout), redirected (3xx), ok (2xx).
     *
     * Completely free — no third-party API key required.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkBrokenLinks(string $domain)
    {
        // ── 1. Validate input ────────────────────────────────────────────────────
        $rawDomain = trim($domain);

        // Ensure scheme is present so parse_url works correctly
        if (!preg_match('#^https?://#i', $rawDomain)) {
            $rawDomain = 'https://' . $rawDomain;
        }

        $parsed = parse_url($rawDomain);
        if (empty($parsed['host'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid domain provided.',
            ], 422);
        }

        // Canonical base URL (scheme + host, no trailing slash)
        $baseUrl = rtrim(($parsed['scheme'] ?? 'https') . '://' . $parsed['host'], '/');

        // ── 2. Fetch homepage HTML ───────────────────────────────────────────────
        try {
            $homepageResponse = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; BrokenLinkChecker/1.0)',
                ])
                ->get($baseUrl);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not reach the domain: ' . $e->getMessage(),
            ], 502);
        }

        if ($homepageResponse->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Domain returned HTTP ' . $homepageResponse->status() . '. Cannot crawl.',
            ], 502);
        }

        $html = $homepageResponse->body();

        // ── 3. Extract all <a href="…"> links ───────────────────────────────────
        $links = $this->extractLinks($html, $baseUrl);

        if (empty($links)) {
            return response()->json([
                'success'      => true,
                'domain'       => $baseUrl,
                'total_links'  => 0,
                'broken_count' => 0,
                'message'      => 'No links found on the homepage.',
                'results'      => [],
            ]);
        }

        // ── 4. HEAD-check each link ──────────────────────────────────────────────
        $results      = [];
        $brokenCount  = 0;
        $okCount      = 0;
        $redirectCount= 0;

        foreach ($links as $url) {
            $checked = $this->checkSingleLink($url);

            // Classify status
            $statusCode = $checked['status_code'];
            if ($statusCode === 0 || $statusCode >= 400) {
                $checked['status_label'] = 'broken';
                $brokenCount++;
            } elseif ($statusCode >= 300) {
                $checked['status_label'] = 'redirect';
                $redirectCount++;
            } else {
                $checked['status_label'] = 'ok';
                $okCount++;
            }

            $results[] = $checked;
        }

        // ── 5. Sort: broken first, then redirects, then ok ───────────────────────
        usort($results, function ($a, $b) {
            $order = ['broken' => 0, 'redirect' => 1, 'ok' => 2];
            return ($order[$a['status_label']] ?? 3) <=> ($order[$b['status_label']] ?? 3);
        });

        // ── 6. Return structured response ────────────────────────────────────────
        return response()->json([
            'success'        => true,
            'domain'         => $baseUrl,
            'total_links'    => count($links),
            'broken_count'   => $brokenCount,
            'redirect_count' => $redirectCount,
            'ok_count'       => $okCount,
            'results'        => $results,
        ]);
    }

    // ============================================================
    //  PRIVATE HELPERS  (add these as private methods in the class)
    // ============================================================

    /**
     * Extract unique, absolute links from an HTML string.
     *
     * - Resolves relative URLs against $baseUrl.
     * - Strips fragment-only (#anchor) and mailto:/tel: links.
     * - Deduplicates the list.
     *
     * @param  string  $html
     * @param  string  $baseUrl  e.g. "https://example.com"
     * @return array<string>
     */
    private function extractLinks(string $html, string $baseUrl): array
    {
        $links = [];

        // Match all href attributes
        preg_match_all('/<a\s[^>]*href=["\']([^"\'#][^"\']*)["\'][^>]*>/i', $html, $matches);

        foreach ($matches[1] ?? [] as $href) {
            $href = trim($href);

            // Skip non-http schemes
            if (preg_match('#^(mailto:|tel:|javascript:|data:)#i', $href)) {
                continue;
            }

            // Resolve relative URLs
            if (!preg_match('#^https?://#i', $href)) {
                $href = $href[0] === '/'
                    ? $baseUrl . $href
                    : $baseUrl . '/' . $href;
            }

            // Remove query & fragment for deduplication
            $cleanUrl = strtok($href, '#');
            if ($cleanUrl) {
                $links[] = $cleanUrl;
            }
        }

        // Deduplicate while preserving order
        return array_values(array_unique($links));
    }

    /**
     * Perform a HEAD request on a single URL and return its status info.
     *
     * Falls back to GET if the server doesn't support HEAD.
     *
     * @param  string  $url
     * @return array{url: string, status_code: int, status_text: string, response_time_ms: int}
     */
    private function checkSingleLink(string $url): array
    {
        $start = microtime(true);

        try {
            // Try HEAD first (faster, no body download)
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; BrokenLinkChecker/1.0)',
                ])
                ->head($url);

            $statusCode = $response->status();

            // Some servers return 405 Method Not Allowed for HEAD → retry with GET
            if ($statusCode === 405) {
                $response   = Http::timeout(10)->withOptions(['stream' => true])->get($url);
                $statusCode = $response->status();
            }

            $statusText = $this->httpStatusText($statusCode);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $statusCode = 0;
            $statusText = 'Connection failed: ' . $e->getMessage();
        } catch (\Exception $e) {
            $statusCode = 0;
            $statusText = 'Error: ' . $e->getMessage();
        }

        $elapsed = (int) round((microtime(true) - $start) * 1000);

        return [
            'url'              => $url,
            'status_code'      => $statusCode,
            'status_text'      => $statusText,
            'response_time_ms' => $elapsed,
        ];
    }

    /**
     * Map an HTTP status code to its standard reason phrase.
     *
     * @param  int  $code
     * @return string
     */
    private function httpStatusText(int $code): string
    {
        $phrases = [
            0   => 'Unreachable / Timeout',
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found (Redirect)',
            304 => 'Not Modified',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            408 => 'Request Timeout',
            410 => 'Gone',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];

        return $phrases[$code] ?? 'HTTP ' . $code;
    }

    // ============================================================
    //  ADD THIS METHOD INSIDE SEOAutomationController
    //  Place it after coreWebVitalsform() (around line 214)
    // ============================================================

    /**
    * Website Health & Site Audit
    *
    * Accepts a domain (or full URL) and runs two audits in parallel:
    *   1. Mozilla HTTP Observatory  – security headers, TLS, HTTPS, CSP, etc.
    *   2. Google PageSpeed Insights – Core Web Vitals + Lighthouse for mobile & desktop
    *
    * Route example (add to web.php / api.php):
    *   Route::post('/website-health-audit', [SEOAutomationController::class, 'websiteHealthAudit']);
    *
    * Request body (JSON or form):
    *   { "domain": "https://example.com" }   — full URL  or just  "example.com"
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function websiteHealthAudit(string $domain): \Illuminate\Http\JsonResponse
    {
        $rawDomain = trim($domain);

        // Ensure scheme is present so parse_url works correctly
        if (!preg_match('#^https?://#i', $rawDomain)) {
            $rawDomain = 'https://' . $rawDomain;
        }

        // Normalise: strip scheme + trailing slash to get bare hostname for Observatory
        $host = preg_replace('#^https?://#i', '', $rawDomain);
        $host = rtrim($host, '/');
        // Strip any path — Observatory only accepts a hostname
        $host = explode('/', $host)[0];

        // Full URL for PageSpeed (must have scheme)
        $siteUrl = 'https://' . $host;

        // ── 2. Mozilla HTTP Observatory ─────────────────────────────────────────
        $observatoryData = $this->runMozillaObservatoryScan($host);

        // ── 3. Google PageSpeed Insights (mobile + desktop, all categories) ─────
        $pageSpeedData = $this->runPageSpeedInsights($siteUrl);

        // ── 4. Build & return unified response ──────────────────────────────────
        // return view(
        //     'arihant.seo.ranking_competitor_report',
        //     compact('created_by_user_id', 'client_property_id', 'domain', 'savedReports')
        // );
        return response()->json([
            'success'  => true,
            'domain'   => $host,
            'site_url' => $siteUrl,
            'audits'   => [
                'mozilla_observatory' => $observatoryData,
                'pagespeed_insights'  => $pageSpeedData,
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }


    // ============================================================
    //  PRIVATE HELPERS — add these as private methods in the class
    // ============================================================

    /**
    * Run a Mozilla HTTP Observatory scan and return structured results.
    *
    * Flow:
    *   POST  /analyze?host={host}              → triggers scan, returns scan meta
    *   GET   /analyze?host={host}              → poll until state === FINISHED
    *   GET   /getScanResults?scan={scan_id}    → per-test breakdown
    *
    * Docs: https://developer.mozilla.org/en-US/observatory/docs/api/
    *
    * @param  string  $host  bare hostname, e.g. "example.com"
    * @return array
    */
    private function runMozillaObservatoryScan(string $host): array
    {
        $baseUrl   = 'https://observatory-api.mdn.mozilla.net/api/v2/analyze?host=';
        $maxPolls  = 10;      // maximum polling attempts
        $pollDelay = 3;       // seconds between polls

        try {
            // ── Step 1: Trigger a fresh scan ────────────────────────────────────
            $triggerResponse = Http::timeout(60)
            ->get("{$baseUrl}{$host}");
            

            if ($triggerResponse->failed()) {
                return [
                    'error'   => true,
                    'message' => 'Observatory trigger failed: HTTP ' . $triggerResponse->status(),
                    'body'    => $triggerResponse->body(),
                ];
            }

            $scanMeta = $triggerResponse->json();
            // dd($triggerResponse->body());


            // ── Step 2: Poll until scan state is FINISHED ───────────────────────
            $attempt  = 0;
            $scanData = $scanMeta['scan'] ?? [];

            // ── Step 3: Fetch per-test details ──────────────────────────────────
            $scanId       = $scanData['id'] ?? null;
            $testResults  = [];

            if ($scanId) {
                // $testResponse = Http::timeout(30)->get("{$baseUrl}/getScanResults?scan={$scanId}");
                $rawTests = $scanMeta['tests'] ?? [];

                    // Structure each test result clearly
                    foreach ($rawTests as $testName => $test) {
                        $testResults[$testName] = [
                            'pass'        => $test['pass']        ?? null,
                            'score_modifier' => $test['score_modifier'] ?? null,
                            'result'      => $test['result']      ?? null,
                            'description' => $test['score_description'] ?? ($test['description'] ?? null),
                            'data'        => $test['output'] ?? ($test['data'] ?? null),
                        ];
                    }
            }

            // ── Step 4: Build summary ────────────────────────────────────────────
            return [
                'error'       => false,
                'scan_id'     => $scanData['id']        ?? null,
                'grade'       => $scanData['grade']          ?? null,
                'score'       => $scanData['score']          ?? null,
                'score_description' => $scanData['score_description'] ?? null,
                'state'       => $scanData['state']          ?? null,
                'tests_passed'  => $scanData['tests_passed']  ?? null,
                'tests_failed'  => $scanData['tests_failed']  ?? null,
                'tests_quantity'=> $scanData['tests_quantity'] ?? null,
                'likelihood_indicator' => $scanData['likelihood_indicator'] ?? null,
                'response_headers' => $scanData['response_headers'] ?? null,
                'scanned_at'  => $scanData['scanned_at']       ?? null,
                'algorithm_version' => $scanData['algorithm_version'] ?? null,
                'tests'       => $testResults,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'error'   => true,
                'message' => 'Observatory connection error: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'error'   => true,
                'message' => 'Observatory unexpected error: ' . $e->getMessage(),
            ];
        }
    }


    /**
    * Run Google PageSpeed Insights for both mobile & desktop.
    *
    * Fetches: performance, accessibility, best-practices, seo
    * Returns all Lighthouse audit details plus category scores.
    *
    * Docs: https://developers.google.com/speed/docs/insights/v5/get-started
    *
    * @param  string  $url  full URL with scheme, e.g. "https://example.com"
    * @return array
    */
    private function runPageSpeedInsights(string $url): array
    {
        $apiKey    = env('PAGESPEED_API_KEY');
        $results   = [];
        $categories = ['performance', 'accessibility', 'best-practices', 'seo'];

        foreach (['mobile', 'desktop'] as $strategy) {
            $queryParams = [
                'url'      => $url,
                'key'      => $apiKey,
                'strategy' => $strategy,
            ];

            // Manually append categories (important)
            $queryString = http_build_query($queryParams);

            foreach ($categories as $category) {
                $queryString .= '&category=' . urlencode($category);
            }

            $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?' . $queryString;
            // dd($apiUrl);
            try {
                $response = Http::timeout(120)->get($apiUrl);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $results[$strategy] = [
                    'error'   => true,
                    'message' => 'PageSpeed connection error: ' . $e->getMessage(),
                ];
                continue;
            }

            if ($response->failed()) {
                $results[$strategy] = [
                    'error'   => true,
                    'message' => 'PageSpeed API failed for ' . $strategy . ': HTTP ' . $response->status(),
                    'body'    => $response->json(),
                ];
                continue;
            }

            $data       = $response->json();
            $lhr        = $data['lighthouseResult']   ?? [];
            $loadingExp = $data['loadingExperience']  ?? [];
            $audits     = $lhr['audits']              ?? [];
            $categories = $lhr['categories']          ?? [];

            // Helper closure: extract a single audit's key fields
            $audit = fn(string $key) => [
                'title'        => $audits[$key]['title']        ?? null,
                'description'  => $audits[$key]['description']  ?? null,
                'display_value'=> $audits[$key]['displayValue'] ?? '—',
                'numeric_value'=> $audits[$key]['numericValue'] ?? null,
                'score'        => $audits[$key]['score']        ?? null,
                'score_display_mode' => $audits[$key]['scoreDisplayMode'] ?? null,
            ];

            // ── Category Scores ──────────────────────────────────────────────────
            $categoryScores = [];
            foreach (['performance', 'accessibility', 'best-practices', 'seo'] as $cat) {
                $catData = $categories[$cat] ?? [];
                $categoryScores[$cat] = [
                    'title' => $catData['title'] ?? $cat,
                    'score' => isset($catData['score']) ? (int) round($catData['score'] * 100) : null,
                    'description' => $catData['description'] ?? null,
                ];
            }

            // ── Core Web Vitals (Lab Data) ───────────────────────────────────────
            $coreWebVitals = [
                'first_contentful_paint'    => $audit('first-contentful-paint'),
                'largest_contentful_paint'  => $audit('largest-contentful-paint'),
                'total_blocking_time'       => $audit('total-blocking-time'),
                'cumulative_layout_shift'   => $audit('cumulative-layout-shift'),
                'speed_index'               => $audit('speed-index'),
                'time_to_interactive'       => $audit('interactive'),
                'time_to_first_byte'        => $audit('server-response-time'),
                'interaction_to_next_paint' => $audit('interaction-to-next-paint'),
            ];

            // ── Diagnostics & Opportunity Audits ────────────────────────────────
            $diagnostics  = [];
            $opportunities= [];

            foreach ($audits as $auditKey => $auditItem) {
                // Skip already-captured CWV metrics
                if (in_array($auditKey, [
                    'first-contentful-paint', 'largest-contentful-paint',
                    'total-blocking-time', 'cumulative-layout-shift',
                    'speed-index', 'interactive', 'server-response-time',
                    'interaction-to-next-paint',
                ])) {
                    continue;
                }

                $mode  = $auditItem['scoreDisplayMode'] ?? '';
                $score = $auditItem['score'] ?? null;

                $entry = [
                    'title'         => $auditItem['title']        ?? null,
                    'description'   => $auditItem['description']  ?? null,
                    'display_value' => $auditItem['displayValue'] ?? null,
                    'score'         => $score,
                    'score_display_mode' => $mode,
                    'details_type'  => $auditItem['details']['type'] ?? null,
                    'items'         => $auditItem['details']['items'] ?? null,
                ];

                if ($mode === 'opportunity') {
                    $entry['savings_ms'] = $auditItem['details']['overallSavingsMs'] ?? null;
                    $opportunities[$auditKey] = $entry;
                } elseif (!in_array($mode, ['notApplicable', 'manual'])) {
                    $diagnostics[$auditKey] = $entry;
                }
            }

            // ── Field Data (CrUX — real-user data if available) ─────────────────
            $fieldData = [];
            if (!empty($loadingExp['metrics'])) {
                foreach ($loadingExp['metrics'] as $metricKey => $metricVal) {
                    $fieldData[$metricKey] = [
                        'category'     => $metricVal['category']      ?? null,
                        'percentile'   => $metricVal['percentile']    ?? null,
                        'distributions'=> $metricVal['distributions'] ?? null,
                    ];
                }
            }

            // ── Lighthouse Meta ──────────────────────────────────────────────────
            $meta = [
                'lighthouse_version'  => $lhr['lighthouseVersion']  ?? null,
                'fetch_time'          => $lhr['fetchTime']          ?? null,
                'requested_url'       => $lhr['requestedUrl']       ?? $url,
                'final_url'           => $lhr['finalUrl']           ?? null,
                'user_agent'          => $lhr['userAgent']          ?? null,
                'environment'         => $lhr['environment']        ?? null,
                'stack_packs'         => array_map(
                    fn($sp) => ['id' => $sp['id'] ?? null, 'title' => $sp['title'] ?? null],
                    $lhr['stackPacks'] ?? []
                ),
            ];

            $results[$strategy] = [
                'error'          => false,
                'meta'           => $meta,
                'category_scores'=> $categoryScores,
                'core_web_vitals'=> $coreWebVitals,
                'opportunities'  => $opportunities,
                'diagnostics'    => $diagnostics,
                'field_data_crux'=> $fieldData,
            ];

            // Reset for next iteration (variable was reused)
            $categories = ['performance', 'accessibility', 'best-practices', 'seo'];
        }

        return $results;
    }

    public function create() {}
    public function store(Request $request) {}
    public function show(string $id) {}
    public function edit(string $id) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}