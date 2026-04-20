<?php

namespace App\Http\Controllers;

use App\Helpers\GeneralHelper;
use Illuminate\Http\Request;

class SEOAutomationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function rankingReport()
    {
        return view('arihant.seo.ranking_report');
    }

    public function rankingReportform(Request $request)
{
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
