<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminNewsController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        abort_if($status !== '' && $status !== 'archived_scheduled' && ! in_array($status, NewsPost::STATUSES, true), 404);

        return view('admin.news.index', [
            'posts' => NewsPost::query()
                ->with('author:id,name')
                ->when($status === 'archived_scheduled', fn ($query) => $query->whereIn('status', ['archived', 'scheduled']))
                ->when($status !== '' && $status !== 'archived_scheduled', fn ($query) => $query->where('status', $status))
                ->orderByRaw("CASE status WHEN 'draft' THEN 0 WHEN 'scheduled' THEN 1 WHEN 'published' THEN 2 ELSE 3 END")
                ->orderByDesc('published_at')
                ->orderByDesc('updated_at')
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'counts' => NewsPost::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status'),
            'statuses' => NewsPost::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('admin.news.edit', ['post' => new NewsPost(), 'categories' => NewsPost::CATEGORIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $post = new NewsPost(['user_id' => $request->user()->id]);

        return $this->save($request, $post);
    }

    public function edit(NewsPost $post): View
    {
        return view('admin.news.edit', ['post' => $post, 'categories' => NewsPost::CATEGORIES]);
    }

    public function update(Request $request, NewsPost $post): RedirectResponse
    {
        return $this->save($request, $post);
    }

    public function setStatus(Request $request, NewsPost $post): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'published', 'archived'])]]);
        $post->status = $data['status'];
        $post->published_at = $data['status'] === 'published' ? ($post->published_at ?? now()) : $post->published_at;
        $post->save();

        return back()->with('status', 'News item status updated.');
    }

    /**
     * Restore an archived news post back to draft status.
     */
    public function unarchive(NewsPost $post): RedirectResponse
    {
        if ($post->status === 'archived') {
            $post->update([
                'status' => 'draft',
                'published_at' => null, // Reset date so it doesn't instantly publish
            ]);
        }

        return back()->with('status', 'News item restored to draft. You can now edit and re-publish it.');
    }

    private function save(Request $request, NewsPost $post): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('news_posts', 'slug')->ignore($post->id)],
            'excerpt' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:50000'],
            'category' => ['required', Rule::in(array_keys(NewsPost::CATEGORIES))],
            'severity' => ['required', Rule::in(NewsPost::SEVERITIES)],
            'action' => ['required', Rule::in(['draft', 'publish', 'schedule'])],
            'published_at' => ['required_if:action,schedule', 'nullable', 'date', 'after:now'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
            'is_banner' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer', 'min:0'],
        ]);

        $slug = Str::slug($data['slug'] ?: $data['title']);
        $baseSlug = $slug !== '' ? $slug : 'notice';
        $suffix = 2;
        while (NewsPost::query()->where('slug', $slug)->when($post->exists, fn ($query) => $query->whereKeyNot($post->id))->exists()) {
            $slug = $baseSlug . '-' . $suffix++;
        }

        $attachments = $post->attachments ?? [];
        foreach ($data['remove_attachments'] ?? [] as $removeIndex) {
            if (isset($attachments[$removeIndex])) {
                Storage::disk('local')->delete($attachments[$removeIndex]['path']);
                unset($attachments[$removeIndex]);
            }
        }
        $attachments = array_values($attachments);

        $post->fill([
            'user_id' => $post->user_id ?: $request->user()->id,
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'],
            'body' => $data['body'],
            'category' => $data['category'],
            'severity' => $data['severity'],
            'is_banner' => $request->boolean('is_banner'),
            'status' => match ($data['action']) {
                'publish' => 'published',
                'schedule' => 'scheduled',
                default => 'draft',
            },
            'published_at' => match ($data['action']) {
                'publish' => now(),
                'schedule' => $data['published_at'],
                default => null,
            },
            'expires_at' => $data['expires_at'] ?? null,
            'attachments' => $attachments,
        ]);
        $post->save();

        foreach ($request->file('attachments', []) as $file) {
            $attachments[] = [
                'path' => $file->store('news/' . $post->id, 'local'),
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
            ];
        }
        $post->attachments = $attachments;
        $post->save();

        // Determine success message based on the clicked button
        $message = match ($data['action']) {
            'publish' => 'News item published successfully.',
            'schedule' => 'News item scheduled successfully.',
            default => 'Draft saved successfully.',
        };

        // Redirect to the index list instead of staying on the edit page
        return redirect()->route('admin.news.index')->with('status', $message);
    }
}