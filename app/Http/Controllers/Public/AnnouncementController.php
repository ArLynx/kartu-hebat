<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $query = Announcement::query()->published()->latest('published_at');

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim().'%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('excerpt', 'like', $search)
                    ->orWhere('body', 'like', $search);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        $announcements = $query->paginate(9)->withQueryString();

        return view('public.announcements.index', compact('announcements'));
    }

    public function show(string $slug)
    {
        $announcement = Announcement::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $recentAnnouncements = Announcement::query()
            ->published()
            ->where('id', '!=', $announcement->id)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('public.announcements.show', compact('announcement', 'recentAnnouncements'));
    }

    public function download(string $slug): StreamedResponse
    {
        $announcement = Announcement::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless(
            $announcement->attachment_path && Storage::disk('public')->exists($announcement->attachment_path),
            404,
            'Berkas lampiran pengumuman tidak ditemukan.'
        );

        $downloadName = 'Pengumuman-'.Str::slug($announcement->title).'.pdf';

        return Storage::disk('public')->download($announcement->attachment_path, $downloadName);
    }
}
