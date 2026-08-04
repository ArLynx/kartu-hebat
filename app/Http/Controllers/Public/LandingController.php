<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\DocumentType;

class LandingController extends Controller
{
    public function __invoke()
    {
        return view('public.landing', [
            'announcements' => Announcement::query()->published()->latest('published_at')->limit(3)->get(),
            'documentTypes' => DocumentType::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'registeredCount' => Application::query()
                ->where('periode', config('kartu_hebat.current_period'))
                ->whereNot('status', 'DRAFT')
                ->count(),
        ]);
    }
}
