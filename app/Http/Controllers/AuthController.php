<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
 
        return view('auth.login');
    }

    public function login(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with(['role', 'team'])
                    ->where('email', $request->email)
                    ->first();

        if (! $user) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        try {
            $passwordMatch = Hash::check($request->password, $user->password);
        } catch (\RuntimeException $e) {
            return back()
                ->withErrors(['email' => 'Something went wrong with password. Please contact admin or reset password.'])
                ->withInput();
        }

        if (! $passwordMatch) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => 'Your account is disabled.'])->withInput();
        }

        if (! $user->role || ! $user->role->is_active) {
            return back()->withErrors(['email' => 'Your role is inactive.'])->withInput();
        }

        if ($user->team_id && (! $user->team || ! $user->team->is_active)) {
            return back()->withErrors(['email' => 'Your team is inactive.'])->withInput();
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
    
    public function index()
    {
        $user = Auth::user(); 

        return view('dashboard', compact('user'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
 
        $request->session()->invalidate();
        $request->session()->regenerateToken();
 
        return redirect()->route('login');
    }
}
