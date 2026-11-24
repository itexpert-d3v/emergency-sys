@extends('admin.partials.ops-theme')

@section('title', 'User management · Kalibo MDRRMO')

@section('styles')
    <style>
        /* Fix: Lock the avatar size so it doesn't blow up the table */
        .user-table-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--nm-raised-xs);
            border: 1px solid var(--nm-lo);
        }
        .user-table-avatar-fallback {
            background-color: var(--nm-bg);
            color: var(--nm-ink-3);
            font-size: 1rem;
            font-weight: 800;
        }
        
        /* Ensure table cells don't wrap awkwardly */
        .nm-table th { white-space: nowrap; }
    </style>
@endsection

@section('content')
    <header class="operations-page-head">
        <div>
            <h1>User management</h1>
            <p>Manage operations accounts and access roles.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="nm-btn nm-btn-primary">Add user</a>
    </header>

    @if (session('status'))
        <p class="mb-4 rounded-xl border border-[var(--nm-green)] bg-[var(--nm-bg)] px-4 py-3 text-sm font-bold text-[var(--nm-green)] shadow-[var(--nm-inset-sm)]" role="status">
            {{ session('status') }}
        </p>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-[var(--nm-red)] bg-[var(--nm-bg)] px-4 py-3 text-sm text-[var(--nm-red)] shadow-[var(--nm-inset-sm)]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="ops-card">
        <div class="ops-card-head">
            <h2 class="ops-panel-title">System users</h2>
            <p class="nm-muted">{{ $users->total() }} accounts</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full min-w-[46rem] text-left text-sm nm-table">
                <thead class="border-b border-[var(--nm-lo)] text-xs uppercase tracking-wider text-[var(--nm-ink-3)]">
                    <tr>
                        <th class="px-5 py-4">Photo</th>
                        <th class="px-5 py-4">Username</th>
                        <th class="px-5 py-4">Name</th>
                        <th class="px-5 py-4">Email</th>
                        <th class="px-5 py-4">Role</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--nm-lo)]">
                @forelse ($users as $user)
                    <tr class="transition-colors hover:bg-black/5 dark:hover:bg-white/5">
                        <td class="px-5 py-3">
                            @if ($user->avatar_path)
                                <img src="{{ asset('storage/' . $user->avatar_path) }}" alt="{{ $user->name }} profile picture" class="user-table-avatar">
                            @else
                                <span class="user-table-avatar user-table-avatar-fallback" aria-label="No profile picture">
                                    {{ \Illuminate\Support\Str::upper(mb_substr($user->name, 0, 1)) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 font-semibold text-[var(--nm-ink)]">{{ $user->username ?: 'Not set' }}</td>
                        <td class="px-5 py-4 text-[var(--nm-ink-2)]">{{ $user->name }}</td>
                        <td class="px-5 py-4 text-[var(--nm-ink-2)]">{{ $user->email }}</td>
                        <td class="px-5 py-4">
                            <span class="nm-badge {{ $user->role === 'admin' ? 'b-amber' : 'b-sky' }}">{{ ucfirst($user->role) }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="inline-flex items-center justify-end gap-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="nm-btn nm-btn-sm">Edit</a>
                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Remove this account?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="nm-btn nm-btn-sm nm-btn-danger">Remove</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-[var(--nm-ink-3)]">No user accounts found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="ops-card-foot">{{ $users->links() }}</div>
        @endif
    </section>
@endsection