<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Sanity\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            return new Client([
                'projectId' => config('services.sanity.project_id'),
                'dataset' => config('services.sanity.dataset'),
                'token' => config('services.sanity.token'),
                'apiVersion' => '2019-01-29',
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);


    }
}
