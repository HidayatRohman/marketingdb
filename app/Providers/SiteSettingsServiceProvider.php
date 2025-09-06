<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class SiteSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share site settings globally with Inertia
        Inertia::share([
            'siteMeta' => function () {
                return [
                    'title' => SiteSetting::get('site_title', config('app.name')),
                    'description' => SiteSetting::get('site_description', 'Marketing Database Management System'),
                ];
            }
        ]);

        // Share site settings with views
        view()->composer('*', function ($view) {
            $view->with([
                'siteTitle' => SiteSetting::get('site_title', config('app.name')),
                'siteFavicon' => SiteSetting::get('site_favicon'),
            ]);
        });
    }
}
