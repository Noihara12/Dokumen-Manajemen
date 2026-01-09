<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
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
        // Gate untuk membuat Surat Masuk - hanya admin dan tata usaha
        Gate::define('create-surat-masuk', function ($user) {
            return !$user->isStaff();
        });

        // Gate untuk edit Surat Keluar - admin, tata usaha, dan staff
        Gate::define('edit-surat-keluar', function ($user) {
            return $user->isAdmin() || $user->isTataUsaha() || $user->isStaff();
        });

        // Gate untuk manage Disposisi - hanya admin dan tata usaha
        Gate::define('manage-disposisi', function ($user) {
            return $user->isTataUsaha() || $user->isAdmin();
        });

        // Gate untuk view Laporan - hanya admin dan tata usaha
        Gate::define('view-laporan', function ($user) {
            return !$user->isStaff();
        });

        // Gate untuk manage Users - hanya admin
        Gate::define('manage-users', function ($user) {
            return $user->isAdmin();
        });
    }
}
