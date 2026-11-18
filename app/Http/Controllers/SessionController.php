<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->destination(Auth::user()->role);
        }

        return view('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $user = Auth::user();
        if (! in_array($user->role, ['admin', 'staff', 'mdrrmo_officer', 'information_officer'], true)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => ['This account is not authorized to access the operations portal.'],
            ]);
        }

        $request->session()->regenerate();

        return $this->destination($user->role);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function destination(string $role): RedirectResponse
    {
        return redirect()->route(match ($role) {
            'admin', 'staff' => 'operations.dashboard',
            default => 'admin.news.index',
        });
    }
}