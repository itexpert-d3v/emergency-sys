<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.edit', ['user' => new User()]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['password'] = Hash::make($data['password']);
        User::query()->create($data);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate($this->rules($user));
        if ($data['role'] !== 'admin' && $user->role === 'admin' && ! $this->anotherAdminExists($user)) {
            return back()->withErrors(['role' => 'The system must keep at least one administrator.'])->withInput();
        }

        if (($data['password'] ?? '') === '') {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot remove your own account.']);
        }

        if ($user->role === 'admin' && ! $this->anotherAdminExists($user)) {
            return back()->withErrors(['user' => 'The system must keep at least one administrator.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User removed.');
    }

    private function rules(?User $user = null): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user?->id)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:12', 'confirmed'],
        ];
    }

    private function anotherAdminExists(User $user): bool
    {
        return User::query()->where('role', 'admin')->whereKeyNot($user->id)->exists();
    }
}