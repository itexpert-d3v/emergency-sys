@extends('admin.partials.ops-theme')

@section('title', 'Safety and deleted reports · Admin')

@section('content')
            <header class="operations-page-head">
                <div>
                    <h1>Safety &amp; deleted reports</h1>
                    <p>Manage blocked reporter identifiers and restore reports removed from the active queue.</p>
                </div>
            </header>

            @if (session('status'))
                <p class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">{{ session('status') }}</p>
            @endif

            <section class="ops-card mb-5 p-5 sm:p-6" aria-labelledby="bans-heading">
                <div class="border-b border-slate-200 pb-4 dark:border-slate-700">
                    <h2 id="bans-heading" class="text-base font-black text-slate-900 dark:text-white">Blocked identifiers</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Phone bans remain until manually lifted. IP bans expire automatically after 24 hours.</p>
                </div>

                <div class="grid gap-5 pt-4 lg:grid-cols-2">
                    <section aria-labelledby="phone-bans-heading">
                        <h3 id="phone-bans-heading" class="mb-3 text-sm font-bold text-slate-800 dark:text-slate-100">Phone numbers</h3>
                        <form method="POST" action="{{ route('admin.phone-bans.store') }}" class="mb-3 flex flex-wrap gap-2">
                            @csrf
                            <label class="sr-only" for="ban-phone-number">Phone number</label>
                            <input id="ban-phone-number" name="phone_number" type="tel" placeholder="+63…" required class="ops-input min-w-40 flex-1">
                            <button type="submit" class="ops-btn ops-btn-primary ops-btn-sm">Ban number</button>
                        </form>
                        @error('phone_number')<p class="mb-3 text-sm font-semibold text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                        <ul class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @forelse ($bannedPhones as $bannedPhone)
                                <li class="flex items-center justify-between gap-3 py-3">
                                    <code class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $bannedPhone->phone_number }}</code>
                                    <form method="POST" action="{{ route('admin.phone-bans.destroy', $bannedPhone) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ops-btn ops-btn-sm">Unban</button>
                                    </form>
                                </li>
                            @empty
                                <li class="py-3 text-sm text-slate-500 dark:text-slate-400">No phone numbers are banned.</li>
                            @endforelse
                        </ul>
                    </section>

                    <section aria-labelledby="ip-bans-heading">
                        <h3 id="ip-bans-heading" class="mb-3 text-sm font-bold text-slate-800 dark:text-slate-100">IP addresses</h3>
                        <form method="POST" action="{{ route('admin.ip-bans.store') }}" class="mb-3 flex flex-wrap gap-2">
                            @csrf
                            <label class="sr-only" for="ban-ip-address">IP address</label>
                            <input id="ban-ip-address" name="ip_address" type="text" inputmode="text" placeholder="IP address" required class="ops-input min-w-40 flex-1">
                            <button type="submit" class="ops-btn ops-btn-primary ops-btn-sm">Ban IP</button>
                        </form>
                        @error('ip_address')<p class="mb-3 text-sm font-semibold text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                        <ul class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @forelse ($bannedIps as $bannedIp)
                                <li class="flex items-center justify-between gap-3 py-3">
                                    <div>
                                        <code class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $bannedIp->ip_address }}</code>
                                        <p class="mt-1 mb-0 text-xs text-slate-500 dark:text-slate-400">
                                            @if ($bannedIp->expires_at)
                                                Expires {{ $bannedIp->expires_at->diffForHumans() }}
                                            @else
                                                Permanent ban
                                            @endif
                                        </p>
                                        @if ($bannedIp->reason)
                                            <p class="mt-1 mb-0 text-xs text-slate-500 dark:text-slate-400">{{ $bannedIp->reason }} · {{ $bannedIp->violation_count }} violations</p>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('admin.ip-bans.destroy', $bannedIp) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ops-btn ops-btn-sm">Unban</button>
                                    </form>
                                </li>
                            @empty
                                <li class="py-3 text-sm text-slate-500 dark:text-slate-400">No IP addresses are banned.</li>
                            @endforelse
                        </ul>
                    </section>
                </div>
            </section>

            <section class="ops-card p-5 sm:p-6" aria-labelledby="deleted-reports-heading">
                <div class="border-b border-slate-200 pb-4 dark:border-slate-700">
                    <h2 id="deleted-reports-heading" class="text-base font-black text-slate-900 dark:text-white">Deleted reports</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Soft-deleted reports are hidden from active queues and tracking until restored.</p>
                </div>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse ($deletedReports as $report)
                        <li class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="m-0 font-mono text-sm font-bold text-slate-900 dark:text-slate-100">{{ $report->reference_code }}</p>
                                <p class="mt-1 mb-0 text-sm text-slate-700 dark:text-slate-300">{{ $report->category?->nameFor(app()->getLocale(), 'en') ?? 'Unclassified report' }} · {{ $report->reporter_name }} · {{ $report->contact_number }}</p>
                                <p class="mt-1 mb-0 text-xs text-slate-500 dark:text-slate-400">Deleted {{ $report->deleted_at?->diffForHumans() }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.report.restore', $report->id) }}">
                                @csrf
                                <button type="submit" class="ops-btn ops-btn-primary ops-btn-sm">Restore report</button>
                            </form>
                        </li>
                    @empty
                        <li class="py-5 text-sm text-slate-500 dark:text-slate-400">No deleted reports.</li>
                    @endforelse
                </ul>
            </section>

            <section class="ops-card mt-5 p-5 sm:p-6" aria-labelledby="rejected-reports-heading">
                <div class="border-b border-slate-200 pb-4 dark:border-slate-700">
                    <h2 id="rejected-reports-heading" class="text-base font-black text-slate-900 dark:text-white">Rejected reports</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Rejection reasons are retained for review. Reopening returns a report to the incident queue.</p>
                </div>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse ($rejectedReports as $report)
                        <li class="flex flex-col gap-3 py-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <p class="m-0 font-mono text-sm font-bold text-slate-900 dark:text-slate-100">{{ $report->reference_code }}</p>
                                <p class="mt-1 mb-0 text-sm text-slate-700 dark:text-slate-300">{{ $report->category?->nameFor(app()->getLocale(), 'en') ?? 'Unclassified report' }} · {{ $report->reporter_name }} · {{ $report->contact_number }}</p>
                                <p class="mt-2 mb-0 text-sm text-slate-700 dark:text-slate-300"><strong>Reason:</strong> {{ $report->rejection_reason }}</p>
                                <p class="mt-1 mb-0 text-xs text-slate-500 dark:text-slate-400">Rejected {{ $report->rejected_at?->diffForHumans() }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.report.reopen', $report->id) }}">
                                @csrf
                                <button type="submit" class="ops-btn ops-btn-primary ops-btn-sm">Reopen report</button>
                            </form>
                        </li>
                    @empty
                        <li class="py-5 text-sm text-slate-500 dark:text-slate-400">No rejected reports.</li>
                    @endforelse
                </ul>
            </section>
@endsection