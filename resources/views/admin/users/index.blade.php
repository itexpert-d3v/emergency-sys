@extends('admin.partials.ops-theme')

@section('title', 'User management · Kalibo MDRRMO')

@section('content')
        <header class="operations-page-head">
            <div><h1>User management</h1><p>Manage operations accounts and access roles.</p></div>
            <a href="{{ route('admin.users.create') }}" class="ops-btn ops-btn-primary">Add user</a>
        </header>

        @if (session('status'))
            <p class="mb-4 border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200" role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="mb-4 border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-200" role="alert">{{ $errors->first() }}</div>
        @endif

        <section class="ops-card">
            <div class="ops-card-head"><h2 class="ops-panel-title">System users</h2><p class="ops-muted">{{ $users->total() }} accounts</p></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[46rem] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase text-slate-500 dark:border-slate-700 dark:text-slate-400"><tr><th class="px-5 py-3">Photo</th><th class="px-5 py-3">Username</th><th class="px-5 py-3">Name</th><th class="px-5 py-3">Email</th><th class="px-5 py-3">Role</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-5 py-3">
                                @if ($user->avatar_path)
                                    <img src="{{ asset('storage/' . $user->avatar_path) }}" alt="{{ $user->name }} profile picture" class="user-table-avatar">
                                @else
                                    <span class="user-table-avatar user-table-avatar-fallback" aria-label="No profile picture">{{ \Illuminate\Support\Str::upper(mb_substr($user->name, 0, 1)) }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-semibold">{{ $user->username ?: 'Not set' }}</td>
                            <td class="px-5 py-4">{{ $user->name }}</td>
                            <td class="px-5 py-4">{{ $user->email }}</td>
                            <td class="px-5 py-4"><span class="ops-badge {{ $user->role === 'admin' ? 'b-amber' : 'b-sky' }}">{{ ucfirst($user->role) }}</span></td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="ops-btn ops-btn-sm">Edit</a>
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Remove this account?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="ops-btn ops-btn-sm ops-btn-danger">Remove</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No user accounts found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())<div class="ops-card-foot">{{ $users->links() }}</div>@endif
        </section>
@endsection