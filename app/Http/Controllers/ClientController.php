<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::with('team:id,name')->get();
        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        $users = User::where('is_active', 1)
                     ->where('role_id', '!=', 1)
                     ->orderBy('name')
                     ->get();
        return view('clients.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|max:191|unique:clients,email',
            'phone'    => 'required|string|max:20',
            'slug'     => 'required|string|max:191|unique:clients,slug',
            'industry' => 'required|in:dermatologist,ivf,other',
            'city'     => 'nullable|string|max:100',
            'zip'      => 'nullable|string|max:20',
            'status'   => 'required|in:active,inactive',
            'user_id'  => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $validated['team_id']    = $user->team_id ?? null;
        $validated['created_by'] = Auth::id();

        Client::create($validated);

        return redirect()->route('clients.index')
                         ->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $client->load(['creator:id,name', 'team:id,name', 'user:id,name,email']);
        return view('gms.gmb.clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        $users = User::where('is_active', 1)
                     ->where('role_id', '!=', 1)
                     ->orderBy('name')
                     ->get();
        return view('clients.edit', compact('client', 'users'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => ['required', 'email', 'max:191', Rule::unique('clients', 'email')->ignore($client->id)],
            'phone'    => 'required|string|max:20',
            'slug'     => ['required', 'string', 'max:191', Rule::unique('clients', 'slug')->ignore($client->id)],
            'industry' => 'required|in:dermatologist,ivf,other',
            'city'     => 'nullable|string|max:100',
            'zip'      => 'nullable|string|max:20',
            'status'   => 'required|in:active,inactive',
            'user_id'  => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $validated['team_id'] = $user->team_id ?? null;

        $client->update($validated);

        return redirect()->route('clients.index')
                         ->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client deleted.']);
        }

        return redirect()->route('clients.index')
                         ->with('success', 'Client deleted successfully.');
    }
}