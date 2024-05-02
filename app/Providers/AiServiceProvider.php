<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AiService;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('aiservice', function ($app) {
            return new AiService();
        });
    }
}