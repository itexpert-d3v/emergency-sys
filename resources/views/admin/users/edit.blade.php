@extends('admin.partials.ops-theme')

@section('title'){{ $user->exists ? 'Edit user' : 'Add user' }} · Kalibo MDRRMO@endsection

@section('content')
        <header class="operations-page-head">
            <div><h1>{{ $user->exists ? 'Edit user' : 'Add user' }}</h1><p>Set account details and an operations role.</p></div>
            <a href="{{ route('admin.users.index') }}" class="operations-head-link">Back to users</a>
        </header>
        @if ($errors->any())<div class="mb-4 border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-200" role="alert"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="ops-card max-w-3xl p-5 sm:p-7">
            @csrf
            @if ($user->exists) @method('PUT') @endif
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.field for="username" label="Username" :error="$errors->first('username')"><x-ui.input id="username" name="username" :value="$user->username" minlength="3" maxlength="40" pattern="[A-Za-z0-9._-]+" required class="ops-input" /></x-ui.field>
                <x-ui.field for="name" label="Profile name" :error="$errors->first('name')"><x-ui.input id="name" name="name" :value="$user->name" maxlength="120" required class="ops-input" /></x-ui.field>
                <x-ui.field for="email" label="Email address" :error="$errors->first('email')"><x-ui.input id="email" type="email" name="email" :value="$user->email" maxlength="255" required class="ops-input" /></x-ui.field>
                <x-ui.field for="role" label="Role" :error="$errors->first('role')">
                    <x-ui.select id="role" name="role" required class="ops-input">
                        <option value="staff" @selected(old('role', $user->role ?: 'staff') === 'staff')>Staff</option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field for="password" :label="$user->exists ? 'New password (optional)' : 'Password'" :hint="'At least 12 characters.'" :error="$errors->first('password')"><x-ui.input id="password" type="password" name="password" minlength="12" :required="!$user->exists" autocomplete="new-password" class="ops-input" :old-input="false" /></x-ui.field>
                <x-ui.field for="password_confirmation" label="Confirm password" :error="$errors->first('password_confirmation')"><x-ui.input id="password_confirmation" type="password" name="password_confirmation" minlength="12" autocomplete="new-password" class="ops-input" :old-input="false" /></x-ui.field>
            </div>
            <div class="mt-6 flex justify-end"><button type="submit" class="ops-btn ops-btn-primary">{{ $user->exists ? 'Save user' : 'Create user' }}</button></div>
        </form>
@endsection