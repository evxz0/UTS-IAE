<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
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
        // Paksa semua URL (aset CSS/JS, form action, redirect) menggunakan https://
        // Ini diperlukan saat aplikasi berjalan di belakang ngrok atau reverse proxy HTTPS.
        // APP_URL di .env harus sudah diset ke https:// (URL ngrok) agar ini bekerja.
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
