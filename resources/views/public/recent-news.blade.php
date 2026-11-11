<section class="mt-10 border-y border-white/40 py-7 dark:border-white/40" aria-labelledby="landing-news-heading">
    <div class="flex flex-wrap items-end justify-between gap-3 border-b border-white/40 pb-4 dark:border-white/40">
        <div>
            <h2 id="landing-news-heading" class="text-2xl font-black text-white dark:text-white">Recent news & updates</h2>
            <p class="mt-2 text-sm text-white dark:text-white">Latest public notices from the MDRRMO</p>
        </div>
        <a href="{{ route('news.index') }}" class="inline-flex min-h-10 items-center border border-white px-4 py-2 text-sm font-bold text-white hover:bg-white/15">View all news</a>
    </div>

    @if ($news->isNotEmpty())
        <ol class="divide-y divide-white/30 dark:divide-white/30">
            @foreach ($news as $post)
                <li>
                    <a href="{{ route('news.show', $post->slug) }}" class="grid gap-3 py-4 text-white transition-colors hover:bg-white/15 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center sm:gap-5">
                        <time datetime="{{ $post->published_at->toIso8601String() }}" class="text-xs font-black uppercase tracking-[0.14em] text-white dark:text-white">{{ $post->published_at->format('M j, Y') }}</time>
                        <span class="min-w-0">
                            <strong class="block truncate text-base font-black text-white dark:text-white">{{ $post->title }}</strong>
                            <span class="mt-1 block text-xs font-bold uppercase tracking-[0.12em] text-white dark:text-white">{{ \App\Models\NewsPost::CATEGORIES[$post->category] ?? $post->category }}</span>
                        </span>
                        <svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" class="h-4 w-4 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12 12 4M5 4h7v7"/></svg>
                    </a>
                </li>
            @endforeach
        </ol>
    @else
        <p class="py-5 text-sm leading-6 text-white">No public updates are published right now. Check the advisories page for the latest notices.</p>
    @endif
</section>
