<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

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
        // Memaksa HTTPS jika berada di environment production (Render)
        if (config('app.env') === 'production' || env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        // Di lokal/test: gagal keras jika ada query N+1 (lazy loading di dalam loop) atau atribut salah ketik.
        Model::shouldBeStrict(! $this->app->isProduction());

        Paginator::defaultView('vendor.pagination.talentrank');

        // MySQL/MariaDB lama (utf8mb4) membatasi panjang index; perbaikan Rangga (ffa6ac4) yang sempat terhapus.
        Schema::defaultStringLength(191);
    }
}