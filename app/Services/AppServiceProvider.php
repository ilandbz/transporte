<?php

namespace App\Providers;

use App\Services\DniRucApiService;
use App\Services\GpsTrackingService;
use App\Services\SunatGreenterService;
use App\Services\SyncService;
use App\Services\TicketService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SunatGreenterService::class);
        $this->app->singleton(DniRucApiService::class);
        $this->app->singleton(GpsTrackingService::class);
        $this->app->singleton(SyncService::class);
        $this->app->singleton(TicketService::class);
    }

    public function boot(): void
    {
        //
    }
}
