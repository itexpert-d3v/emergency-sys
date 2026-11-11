<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $post->excerpt }}">
    <title>{{ $post->title }} · MDRRMO News</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-surface min-h-screen bg-slate-50 text-slate-800 antialiased dark:bg-[#18181b] dark:text-slate-100" style="background-image:url('{{ asset('assets/lgu kalibo.jpg') }}');background-size:cover;background-position:center;background-attachment:fixed">
    <div class="app-photo-canvas min-h-screen">
        @include('public.navigation')
        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6"><a href="{{ route('news.index') }}" class="text-sm font-bold text-blue-800 underline underline-offset-4 dark:text-blue-300">All news and advisories</a>
            <article class="mt-5 grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]"><div class="min-w-0"><div class="flex flex-wrap items-center gap-3 text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300"><span class="text-blue-800 dark:text-blue-300">{{ \App\Models\NewsPost::CATEGORIES[$post->category] ?? $post->category }}</span><span aria-hidden="true">·</span><time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->published_at?->format('F j, Y · g:i A') }}</time>@if ($post->severity !== 'normal')<span class="rounded-[3px] px-2 py-1 text-white {{ $post->severity === 'critical' ? 'bg-red-800' : 'bg-amber-700' }}">{{ ucfirst($post->severity) }}</span>@endif</div>
                <h1 class="mt-4 max-w-4xl text-3xl font-black leading-tight text-slate-950 dark:text-white sm:text-4xl">{{ $post->title }}</h1><p class="mt-4 max-w-3xl text-lg leading-7 text-slate-700 dark:text-slate-200">{{ $post->excerpt }}</p><p class="mt-4 border-b border-slate-300 pb-5 text-xs text-slate-600 dark:border-slate-700 dark:text-slate-300">Published by {{ $post->author?->name ?? 'MDRRMO' }}</p>
                <div class="news-prose mt-7 max-w-3xl text-base leading-7 text-slate-800 dark:text-slate-100">{!! $bodyHtml !!}</div>
                @if (count($post->attachments ?? []))
                    <section class="mt-9 border-t border-slate-300 pt-5 dark:border-slate-700" aria-labelledby="attachments-heading">
                        <h2 id="attachments-heading" class="text-lg font-black text-slate-950 dark:text-white">Official attachments</h2>
                        <div class="mt-4 divide-y divide-slate-300 dark:divide-slate-700">
                            @foreach ($post->attachments as $index => $attachment)
                                @php($attachmentUrl = route('news.attachment', ['slug' => $post->slug, 'attachment' => $index]))
                                <div class="py-4">
                                    @if (str_starts_with($attachment['mime'] ?? '', 'image/'))
                                        <figure>
                                            <img src="{{ $attachmentUrl }}" alt="Attached image: {{ $attachment['name'] }}" loading="eager" decoding="async" class="aspect-video max-h-[34rem] w-full border border-slate-300 bg-slate-100 object-contain dark:border-slate-700 dark:bg-slate-800">
                                            <figcaption class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $attachment['name'] }}</figcaption>
                                        </figure>
                                    @endif
                                    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                                        <span class="break-all text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $attachment['name'] }}</span>
                                        <a href="{{ route('news.attachment', ['slug' => $post->slug, 'attachment' => $index, 'download' => 1]) }}" class="text-sm font-black text-blue-800 underline underline-offset-4 dark:text-blue-300">Download file</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
                <section class="mt-8 border-t border-slate-300 pt-5 dark:border-slate-700" aria-label="Share this notice"><h2 class="text-sm font-black text-slate-900 dark:text-white">Share this notice</h2><div class="mt-3 flex flex-wrap gap-4 text-sm font-bold"><a target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('news.show', $post->slug)) }}" class="text-blue-800 underline underline-offset-4 dark:text-blue-300">Facebook</a><a target="_blank" rel="noopener noreferrer" href="https://twitter.com/intent/tweet?url={{ urlencode(route('news.show', $post->slug)) }}&amp;text={{ urlencode($post->title) }}" class="text-blue-800 underline underline-offset-4 dark:text-blue-300">X</a><button type="button" data-copy-share class="text-blue-800 underline underline-offset-4 dark:text-blue-300">Copy link</button><span data-share-status class="sr-only" role="status" aria-live="polite"></span></div></section>
            </div><aside class="lg:sticky lg:top-5 lg:self-start"><div class="border-t border-slate-300 pt-4 dark:border-slate-700"><h2 class="text-sm font-black text-slate-900 dark:text-white">Emergency contacts</h2><p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-200">For immediate danger, call your local emergency hotline. This site does not dispatch responders.</p><a href="{{ route('news.index') }}" class="mt-4 inline-block text-sm font-bold text-blue-800 underline underline-offset-4 dark:text-blue-300">More public updates</a></div></aside></article>
        </main>
        @include('public.footer')
    </div>
    <script>document.querySelector('[data-copy-share]')?.addEventListener('click', async () => { const status = document.querySelector('[data-share-status]'); try { await navigator.clipboard.writeText(window.location.href); status.textContent = 'Link copied.'; } catch { status.textContent = 'Copy is unavailable. Use the address bar to copy this page link.'; } });</script>
</body>
</html>
