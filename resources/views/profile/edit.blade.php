@extends('admin.partials.ops-theme')

@section('title', 'My profile · Kalibo MDRRMO')

@section('content')
        <header class="operations-page-head">
            <div><h1>My profile</h1><p>Update your sign-in name and contact details.</p></div>
        </header>

        @if (session('status'))
            <p class="mb-4 border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200" role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="mb-4 border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-200" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="ops-card max-w-2xl p-5 sm:p-7">
            @csrf
            @method('PUT')
            <div class="mb-6 flex flex-col items-center gap-4 border-b border-slate-300 pb-6 sm:flex-row dark:border-slate-700">
                <div class="profile-avatar-preview" aria-live="polite">
                    @if ($user->avatar_path)
                        <img id="avatar-preview" src="{{ asset('storage/' . $user->avatar_path) }}" alt="Profile picture">
                    @else
                        <img id="avatar-preview" src="" alt="Profile picture preview" class="hidden">
                        <span id="avatar-placeholder" aria-hidden="true">{{ \Illuminate\Support\Str::upper(mb_substr($user->name, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="min-w-0 text-center sm:text-left">
                    <label for="avatar" class="ops-btn ops-btn-sm ops-btn-primary cursor-pointer">Choose profile picture</label>
                    <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" class="sr-only">
                    <p class="ops-muted mt-2">JPG, PNG, or WebP. Maximum 2 MB.</p>
                    @error('avatar')<p class="mt-2 text-sm font-bold text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="username" class="mb-1 block text-sm font-bold">Username</label>
                    <input id="username" name="username" value="{{ old('username', $user->username ?? '') }}" minlength="3" maxlength="40" pattern="[A-Za-z0-9._-]+" autocomplete="username" required class="ops-input w-full">
                </div>
                <div>
                    <label for="name" class="mb-1 block text-sm font-bold">Profile name</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="120" autocomplete="name" required class="ops-input w-full">
                </div>
                <div class="sm:col-span-2">
                    <label for="email" class="mb-1 block text-sm font-bold">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="email" required class="ops-input w-full">
                </div>
            </div>
            <div class="mt-6 flex justify-end"><button type="submit" class="ops-btn ops-btn-primary">Save profile</button></div>
        </form>
        <script>
            (() => {
                const input = document.getElementById('avatar');
                const preview = document.getElementById('avatar-preview');
                const placeholder = document.getElementById('avatar-placeholder');
                if (!input || !preview) return;

                let previewUrl = null;
                input.addEventListener('change', () => {
                    const file = input.files?.[0];
                    if (!file) return;
                    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!allowed.includes(file.type) || file.size > 2 * 1024 * 1024) {
                        input.value = '';
                        input.setCustomValidity('Choose a JPG, PNG, or WebP image smaller than 2 MB.');
                        input.reportValidity();
                        input.setCustomValidity('');
                        return;
                    }
                    if (previewUrl) URL.revokeObjectURL(previewUrl);
                    previewUrl = URL.createObjectURL(file);
                    preview.src = previewUrl;
                    preview.classList.remove('hidden');
                    placeholder?.classList.add('hidden');
                });
                window.addEventListener('pagehide', () => previewUrl && URL.revokeObjectURL(previewUrl));
            })();
        </script>
@endsection