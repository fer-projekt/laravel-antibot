<?php

namespace FerProjekt\AntiBot;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AntiBotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/antibot.php', 'antibot');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'antibot');
        Blade::componentNamespace('FerProjekt\\AntiBot\\View\\Components', 'antibot');

        $this->publishes([
            __DIR__ . '/../config/antibot.php' => config_path('antibot.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/antibot'),
        ], 'views');

        // Alias middleware-a: ->middleware('antibot:form-id')
        $this->app['router']->aliasMiddleware('antibot', \FerProjekt\AntiBot\Http\Middleware\VerifyAntiBot::class);
    }
}
