<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Label;
use App\Models\Mitra;
use App\Models\User;
use App\Policies\BrandPolicy;
use App\Policies\LabelPolicy;
use App\Policies\MitraPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Mitra::class => MitraPolicy::class,
        Brand::class => BrandPolicy::class,
        Label::class => LabelPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }

    /**
     * Register the application's policies.
     */
    public function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
