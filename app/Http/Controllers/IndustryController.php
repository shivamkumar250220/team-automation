<?php

namespace App\Http\Controllers;

use App\Models\Industry;
use Illuminate\Http\Request;

class IndustryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $industry = Industry::get();
        return view('industry.index', compact('industry'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $industry = Industry::get();

        return view('industry.create', compact('industry'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:150',
        ]);
        Industry::create($validated);
        return redirect()->route('industry.index')->with('success', 'Industry created successfully.');
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
        $industry = Industry::findOrFail($id);
        return view('industry.edit', compact('industry'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:150'
        ]);
        $industry = Industry::findOrFail($id);
        $industry->update($validated);

        return redirect()->route('industry.index')->with('success', 'Industry updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $industry = Industry::findOrFail($id);
        $industry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Industry deleted successfully.'
        ]);
    }
}
