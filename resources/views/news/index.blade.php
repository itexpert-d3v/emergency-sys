<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.theme-init')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News &amp; Emergency Updates · MDRRMO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-surface min-h-screen bg-slate-50 text-slate-800 antialiased dark:bg-[#18181b] dark:text-slate-100" style="background-image:url('{{ asset('assets/lgu kalibo.jpg') }}');background-size:cover;background-position:center;background-attachment:fixed">
    <div class="app-photo-canvas min-h-screen">
        @include('public.navigation')
        
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            
            {{-- Emergency Alerts Banner --}}
            @if ($alerts->isNotEmpty())
                <section aria-label="Active emergency alerts" class="mb-8 overflow-hidden rounded-xl border-0 bg-white shadow-sm ring-1 ring-red-900/10 dark:bg-slate-900 dark:ring-red-500/20">
                    <div class="flex flex-wrap items-center justify-between gap-2 bg-[#9e201a] px-5 py-3 text-white">
                        <h1 class="text-xs font-black uppercase tracking-[0.16em]">Active emergency updates</h1>
                        <span class="text-xs font-bold text-red-100">Official MDRRMO notices</span>
                    </div>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($alerts as $alert)
                            <li>
                                <a href="{{ route('news.show', $alert->slug) }}" class="flex flex-col gap-2 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-800/50 sm:flex-row sm:items-center sm:gap-4">
                                    <span class="w-20 shrink-0 text-[11px] font-black uppercase tracking-wider {{ $alert->severity === 'critical' ? 'text-red-700 dark:text-red-400' : 'text-amber-700 dark:text-amber-400' }}">{{ ucfirst($alert->severity) }}</span>
                                    <span class="font-black text-slate-900 dark:text-white">{{ $alert->title }}</span>
                                    <span class="text-sm font-semibold text-slate-500 dark:text-slate-400 sm:ml-auto">{{ $alert->published_at?->diffForHumans() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Page Header & Search --}}
            <div class="mb-8">
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                    <div>
                        <h1 class="text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">News &amp; updates</h1>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-slate-600 dark:text-slate-300">Official advisories, preparedness guidance, and municipal response updates.</p>
                    </div>
                    <form method="GET" action="{{ route('news.index') }}" class="grid w-full gap-2 sm:grid-cols-[minmax(12rem,1fr)_13rem_auto] md:max-w-2xl">
                        <label class="sr-only" for="news-search">Search news</label>
                        <input id="news-search" type="search" name="q" value="{{ $search }}" maxlength="120" placeholder="Search notices..." class="min-h-11 rounded-lg border-0 bg-white px-3 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-blue-500 dark:bg-slate-900 dark:text-white dark:ring-slate-700">
                        <label class="sr-only" for="news-category">Filter by category</label>
                        <select id="news-category" name="category" class="min-h-11 rounded-lg border-0 bg-white px-3 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-blue-500 dark:bg-slate-900 dark:text-white dark:ring-slate-700">
                            <option value="">All categories</option>
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="min-h-11 rounded-lg bg-blue-800 px-6 text-sm font-black text-white shadow-sm transition hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950 dark:hover:bg-blue-300">Search</button>
                    </form>
                </div>
                <nav aria-label="Filter by category" class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('news.index') }}" class="rounded-full px-4 py-1.5 text-xs font-bold transition {{ $category === '' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">All updates</a>
                    @foreach ($categories as $value => $label)
                        <a href="{{ route('news.index', array_filter(['category' => $value, 'q' => $search])) }}" class="rounded-full px-4 py-1.5 text-xs font-bold transition {{ $category === $value ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">{{ $label }}</a>
                    @endforeach
                </nav>
            </div>

            {{-- News Grid --}}
            <section aria-label="Published news and advisories">
                @if($posts->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white/50 py-16 text-center dark:border-slate-700 dark:bg-slate-900/50">
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">No matching updates</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Try a different search term or category.</p>
                        <a href="{{ route('news.index') }}" class="mt-4 inline-block text-sm font-bold text-blue-800 underline underline-offset-4 dark:text-blue-300">Clear all filters</a>
                    </div>
                @else
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($posts as $post)
                            @php
                                // Find the first image attachment for the thumbnail preview
                                $imagePreview = null;
                                foreach ($post->attachments ?? [] as $idx => $attachment) {
                                    if (str_starts_with($attachment['mime'] ?? '', 'image/')) {
                                        $imagePreview = route('news.attachment', ['slug' => $post->slug, 'attachment' => $idx]);
                                        break;
                                    }
                                }
                            @endphp

                            <article class="group flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md dark:bg-slate-900 dark:ring-slate-800">
                                
                                {{-- Image / Placeholder Peek --}}
                                <a href="{{ route('news.show', $post->slug) }}" class="relative block aspect-video overflow-hidden bg-slate-100 dark:bg-slate-800">
                                    @if ($imagePreview)
                                        <img src="{{ $imagePreview }}" alt="Preview for {{ $post->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                    @else
                                        {{-- Clean placeholder if no image exists --}}
                                        <div class="flex h-full w-full flex-col items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800/50 dark:to-slate-800">
                                            <svg class="h-10 w-10 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                            <span class="mt-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">No image attached</span>
                                        </div>
                                    @endif
                                    
                                    @if($post->is_banner)
                                        <span class="absolute top-3 left-3 rounded bg-red-600 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-white shadow-sm">Emergency</span>
                                    @endif
                                </a>

                                <div class="flex flex-1 flex-col p-5">
                                    <div class="mb-3 flex flex-wrap items-center gap-2 text-[11px] font-black uppercase tracking-wider">
                                        <span class="text-blue-700 dark:text-blue-400">{{ $categories[$post->category] ?? $post->category }}</span>
                                        @if ($post->severity !== 'normal')
                                            <span class="text-slate-300 dark:text-slate-600">•</span>
                                            <span class="{{ $post->severity === 'critical' ? 'text-red-700 dark:text-red-400' : 'text-amber-700 dark:text-amber-400' }}">{{ ucfirst($post->severity) }}</span>
                                        @endif
                                    </div>

                                    <h2 class="text-lg font-black leading-tight text-slate-900 dark:text-white">
                                        <a href="{{ route('news.show', $post->slug) }}" class="hover:underline decoration-2 underline-offset-4">{{ $post->title }}</a>
                                    </h2>
                                    
                                    <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                        {{ $post->excerpt }}
                                    </p>

                                    <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-4 dark:border-slate-800">
                                        <time datetime="{{ $post->published_at?->toIso8601String() }}" class="text-xs font-bold text-slate-500 dark:text-slate-400">
                                            {{ $post->published_at?->format('M j, Y') }}
                                        </time>
                                        <a href="{{ route('news.show', $post->slug) }}" class="text-xs font-black text-blue-800 hover:underline dark:text-blue-400">
                                            Read update &rarr;
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
            
            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        </main>
        
        @include('public.footer')
    </div>
</body>
</html>