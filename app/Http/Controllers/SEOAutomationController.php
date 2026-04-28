<?php

namespace App\Http\Controllers;

use App\Helpers\GeneralHelper;
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
                    'error' => 'PageSpeed API request failed for strategy: ' . $strategy . ' — ' . $response->status(),
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
        'spreadsheet_url'    => 'required|string', // Changed from drive_folder_url to spreadsheet_url
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
        // [
        //     'name'    => 'Duplicate H1',
        //     'headers' => ['S.No.', 'Page URL', 'Error', 'Existing H1', 'Recommended H1', 'Comments', 'Status'],
        // ],
        // [
        //     'name'    => 'Multiple H1',
        //     'headers' => ['S.No.', 'Page URL', 'Error', 'H1 Count', 'Existing H1', 'Existing H2', 'Comments', 'Status'],
        // ],
        // [
        //     'name'    => 'Missing Alt Text',
        //     'headers' => ['S.No.	LInk From	URL	Error	Recommended Alt Text	Comments	Date'],
        // ],
        [
            'name'    => 'Oversize Images',
            'headers' => ['S. No', 'Page URL', 'Image URL', 'File Size (KB)', 'Fixed', 'Recommendation', 'Comments'],
        ],
        // [
        //     'name'    => 'Schema',
        //     'headers' => ['S. No', 'Page URL', 'Schema Type', 'Status', 'Notes'],
        // ],
        [
            'name'    => 'Page Speed',
            'headers' => [],
        ],
        // [
        //     'name'    => 'Audit',
        //     'docType' => 'document',
        // ],
        // [
        //     'name' => 'Robots TXT',
        //     'note' => 'Need to Create Robots.txt',
        // ],
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
        $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?' . http_build_query([
            'url'      => $domain,
            'key'      => $apiKey,
            'strategy' => 'mobile',          // mobile gives us the full network graph
            'category' => ['seo', 'best-practices', 'performance'],
        ], '', '&', PHP_QUERY_RFC3986);

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

        foreach (['desktop', 'mobile'] as $strategy) {
            $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?' . http_build_query([
                'url'      => $domain,
                'key'      => $apiKey,
                'strategy' => $strategy,
                'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
            ], '', '&', PHP_QUERY_RFC3986);

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

    public function create() {}
    public function store(Request $request) {}
    public function show(string $id) {}
    public function edit(string $id) {}
    public function update(Request $request, string $id) {}
    public function destroy(string $id) {}
}