<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Auth\Contracts\AuthServiceInterface;
use App\Modules\Auth\Contracts\UserModerationServiceInterface;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\UserModerationService;
use App\Modules\Wallet\Contracts\WalletCreatorInterface;
use App\Modules\Wallet\Services\WalletService;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WalletCreatorInterface::class, WalletService::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(UserModerationServiceInterface::class, UserModerationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
    }
}
