<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News &amp; Emergency Updates · MDRRMO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-surface min-h-screen bg-slate-50 text-slate-800 antialiased dark:bg-[#18181b] dark:text-slate-100" style="background-image:url('{{ asset('assets/lgu kalibo.jpg') }}');background-size:cover;background-position:center;background-attachment:fixed">
    <div class="app-photo-canvas min-h-screen">
        @include('public.navigation')
        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @if ($alerts->isNotEmpty())
                <section aria-label="Active emergency alerts" class="mb-7 overflow-hidden rounded-[6px] border border-red-900/20" style="background:#fff8f2">
                    <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2 text-white" style="background:#9e201a"><h1 class="text-xs font-black uppercase tracking-[0.16em]">Active emergency updates</h1><span class="text-xs font-bold">Official MDRRMO notices</span></div>
                    <ul class="divide-y divide-red-200">@foreach ($alerts as $alert)<li><a href="{{ route('news.show', $alert->slug) }}" class="flex flex-col gap-1 px-4 py-3 text-slate-900 hover:bg-white sm:flex-row sm:items-center sm:gap-4"><span class="w-20 shrink-0 text-xs font-black uppercase {{ $alert->severity === 'critical' ? 'text-red-800' : 'text-amber-800' }}">{{ ucfirst($alert->severity) }}</span><span class="font-black">{{ $alert->title }}</span><span class="text-sm text-slate-700 sm:ml-auto">{{ $alert->published_at?->diffForHumans() }}</span></a></li>@endforeach</ul>
                </section>
            @endif
            <div class="mb-7 border-b border-slate-300 pb-6 dark:border-slate-700">
                <div class="flex flex-col justify-between gap-5 md:flex-row md:items-end"><div><h1 class="text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">News &amp; emergency updates</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-700 dark:text-slate-200">Official advisories, preparedness guidance, and municipal response updates.</p></div>
                    <form method="GET" action="{{ route('news.index') }}" class="grid w-full gap-2 sm:grid-cols-[minmax(12rem,1fr)_13rem_auto] md:max-w-2xl"><label class="sr-only" for="news-search">Search news</label><input id="news-search" type="search" name="q" value="{{ $search }}" maxlength="120" placeholder="Search notices" class="min-h-11 rounded-[4px] border border-slate-300 bg-white px-3 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><label class="sr-only" for="news-category">Filter by category</label><select id="news-category" name="category" class="min-h-11 rounded-[4px] border border-slate-300 bg-white px-3 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white"><option value="">All categories</option>@foreach ($categories as $value => $label)<option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>@endforeach</select><button type="submit" class="min-h-11 rounded-[4px] bg-blue-800 px-5 text-sm font-black text-white hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950">Search</button></form>
                </div>
                <nav aria-label="Filter by category" class="mt-4 flex flex-wrap gap-x-4 gap-y-2"><a href="{{ route('news.index') }}" class="text-sm font-bold {{ $category === '' ? 'text-blue-800 underline underline-offset-4 dark:text-blue-300' : 'text-slate-700 dark:text-slate-200' }}">All updates</a>@foreach ($categories as $value => $label)<a href="{{ route('news.index', array_filter(['category' => $value, 'q' => $search])) }}" class="text-sm font-bold {{ $category === $value ? 'text-blue-800 underline underline-offset-4 dark:text-blue-300' : 'text-slate-700 dark:text-slate-200' }}">{{ $label }}</a>@endforeach</nav>
            </div>
            <section aria-label="Published news and advisories">
                @forelse ($posts as $post)
                    <article class="grid gap-3 border-b border-slate-300 py-5 first:pt-0 dark:border-slate-700 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6"><div class="text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300"><time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->published_at?->format('M j, Y') }}</time><span class="mt-2 block text-blue-800 dark:text-blue-300">{{ $categories[$post->category] ?? $post->category }}</span></div><div><div class="flex flex-wrap items-center gap-x-3 gap-y-1">@if ($post->severity !== 'normal')<span class="text-[11px] font-black uppercase {{ $post->severity === 'critical' ? 'text-red-800 dark:text-red-300' : 'text-amber-800 dark:text-amber-300' }}">{{ ucfirst($post->severity) }}</span>@endif @if ($post->is_banner)<span class="text-[11px] font-black uppercase text-blue-800 dark:text-blue-300">Emergency update</span>@endif</div><h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white"><a href="{{ route('news.show', $post->slug) }}" class="hover:underline underline-offset-4">{{ $post->title }}</a></h2><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-700 dark:text-slate-200">{{ $post->excerpt }}</p><a href="{{ route('news.show', $post->slug) }}" class="mt-3 inline-block text-sm font-black text-blue-800 hover:underline dark:text-blue-300">Read full update</a></div></article>
                @empty
                    <div class="border-y border-dashed border-slate-400 py-12 text-center dark:border-slate-600"><h2 class="text-lg font-black text-slate-900 dark:text-white">No matching updates</h2><p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Try a different search or category.</p><a href="{{ route('news.index') }}" class="mt-4 inline-block text-sm font-bold text-blue-800 underline underline-offset-4 dark:text-blue-300">Clear filters</a></div>
                @endforelse
            </section>
            <div class="mt-6">{{ $posts->links() }}</div>
        </main>
        @include('public.footer')
    </div>
</body>
</html>
