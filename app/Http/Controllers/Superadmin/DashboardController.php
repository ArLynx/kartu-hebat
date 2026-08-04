<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\JenisDokumen;
use App\Models\KategoriBeasiswa;
use App\Models\Periode;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('superadmin.dashboard', [
            'stats' => [
                'categories' => KategoriBeasiswa::query()->count(),
                'activeCategories' => KategoriBeasiswa::query()->where('aktif', true)->count(),
                'documentTypes' => DocumentType::query()->count(),
                'activeDocumentTypes' => DocumentType::query()->where('is_active', true)->count(),
                'integratedDocumentTypes' => JenisDokumen::query()->count(),
                'periods' => Periode::query()->count(),
            ],
            'recentCategories' => KategoriBeasiswa::query()
                ->with('periode')
                ->latest('updated_at')
                ->limit(5)
                ->get(),
            'recentDocumentTypes' => DocumentType::query()
                ->latest('updated_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
