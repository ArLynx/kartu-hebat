<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Announcement::query()->with('creator')->latest('published_at');

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim().'%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('excerpt', 'like', $search);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $status = $request->string('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'draft') {
                $query->where('is_active', false);
            }
        }

        $announcements = $query->paginate(15)->withQueryString();

        return view('superadmin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('superadmin.announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAnnouncement($request);

        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        while (Announcement::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->storePubliclyAs(
                'announcements',
                'lampiran-'.Str::slug($validated['title']).'-'.Str::random(6).'.pdf',
                'public'
            );
        }

        Announcement::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'type' => $validated['type'],
            'excerpt' => $validated['excerpt'],
            'body' => $validated['body'],
            'attachment_path' => $attachmentPath,
            'is_active' => $request->boolean('is_active'),
            'published_at' => $validated['published_at'] ?? now(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('superadmin.announcements.index')
            ->with('success', 'Pengumuman resmi berhasil ditambahkan.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('superadmin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $this->validateAnnouncement($request, $announcement->id);

        $attachmentPath = $announcement->attachment_path;

        if ($request->boolean('remove_attachment')) {
            if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                Storage::disk('public')->delete($attachmentPath);
            }
            $attachmentPath = null;
        }

        if ($request->hasFile('attachment')) {
            if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                Storage::disk('public')->delete($attachmentPath);
            }
            $attachmentPath = $request->file('attachment')->storePubliclyAs(
                'announcements',
                'lampiran-'.Str::slug($validated['title']).'-'.Str::random(6).'.pdf',
                'public'
            );
        }

        $slug = $announcement->slug;
        if ($announcement->title !== $validated['title']) {
            $baseSlug = Str::slug($validated['title']);
            $slug = $baseSlug;
            $counter = 1;
            while (Announcement::where('slug', $slug)->where('id', '!=', $announcement->id)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
        }

        $announcement->update([
            'title' => $validated['title'],
            'slug' => $slug,
            'type' => $validated['type'],
            'excerpt' => $validated['excerpt'],
            'body' => $validated['body'],
            'attachment_path' => $attachmentPath,
            'is_active' => $request->boolean('is_active'),
            'published_at' => $validated['published_at'] ?? $announcement->published_at ?? now(),
        ]);

        return redirect()
            ->route('superadmin.announcements.index')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        if ($announcement->attachment_path && Storage::disk('public')->exists($announcement->attachment_path)) {
            Storage::disk('public')->delete($announcement->attachment_path);
        }

        $announcement->delete();

        return redirect()
            ->route('superadmin.announcements.index')
            ->with('success', 'Pengumuman berhasil dihapus.');
    }

    public function toggle(Announcement $announcement): RedirectResponse
    {
        $announcement->update([
            'is_active' => ! $announcement->is_active,
        ]);

        $statusText = $announcement->is_active ? 'ditampilkan di portal publik' : 'disembunyikan (draf)';

        return back()->with('success', "Status pengumuman berhasil diubah: {$statusText}.");
    }

    public function file(Announcement $announcement): StreamedResponse
    {
        abort_unless(
            $announcement->attachment_path && Storage::disk('public')->exists($announcement->attachment_path),
            404,
            'Berkas lampiran pengumuman tidak ditemukan.'
        );

        return Storage::disk('public')->response(
            $announcement->attachment_path,
            basename($announcement->attachment_path),
            [
                'Content-Type' => 'application/pdf',
            ],
            'inline'
        );
    }

    private function validateAnnouncement(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['informasi', 'panduan', 'hasil'])],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
            'is_active' => ['nullable'],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:2048'],
        ], [
            'title.required' => 'Judul pengumuman wajib diisi.',
            'type.required' => 'Kategori pengumuman wajib dipilih.',
            'type.in' => 'Kategori pengumuman tidak valid.',
            'body.required' => 'Isi teks pengumuman wajib diisi.',
            'attachment.mimes' => 'Lampiran berkas harus berformat PDF.',
            'attachment.max' => 'Ukuran lampiran maksimal 2 MB (2.048 KB).',
        ]);
    }
}
