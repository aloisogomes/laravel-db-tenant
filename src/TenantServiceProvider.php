<?php

namespace AloisoGomes\LaravelDbTenant;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use AloisoGomes\LaravelDbTenant\Services\TenantManager;

class TenantServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Registra o Singleton
        $this->app->singleton('db-tenant', function ($app) {
            return new TenantManager();
        });
    }

    public function boot()
    {
        $manager = $this->app->make('db-tenant');

        // 1. Hook Web: Reseta ao fim da requisição HTTP
        $this->app->terminating(function () use ($manager) {
            $manager->reset();
        });

        // 2. Hook Queue: Reseta após cada Job
        Event::listen([JobProcessed::class, JobFailed::class], function () use ($manager) {
            $manager->reset();
        });
    }
}