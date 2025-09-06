<?php

namespace App\Http\Middleware;

use App\Helpers\RoleHelper;
use App\Models\SiteSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'permissions' => $user ? RoleHelper::getPermissions($user) : null,
                'navigation' => $user ? RoleHelper::getNavigationItems($user) : [],
                'dataScope' => $user ? RoleHelper::getDataScope($user) : 'none',
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'siteSettings' => [
                'site_title' => SiteSetting::get('site_title', 'Laravel Starter Kit'),
                'site_description' => SiteSetting::get('site_description', 'Marketing Database Management System'),
                'site_logo' => SiteSetting::get('site_logo'),
                'site_favicon' => SiteSetting::get('site_favicon'),
            ],
        ];
    }
}
