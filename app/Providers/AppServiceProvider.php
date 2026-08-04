<?php

namespace App\Providers;

use App\Models\Application;
use App\Policies\ApplicationPolicy;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('id');
        Paginator::useTailwind();
        Gate::policy(Application::class, ApplicationPolicy::class);

        View::composer('layouts.portal', function ($view): void {
            $user = auth()->user();

            $view->with([
                'unreadNotifications' => $user?->unreadNotifications()->count() ?? 0,
                'headerNotifications' => $user?->notifications()->latest()->limit(5)->get() ?? collect(),
            ]);
        });
    }
}
