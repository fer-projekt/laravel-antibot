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

        // 1) Namespaced komponentni prostor (trebao bi omogućiti <x-antibot::fields>)
        Blade::componentNamespace('FerProjekt\\AntiBot\\View\\Components', 'antibot');

        // 2) Eksplicitni aliasi kao fallback (radi i na starijim verzijama Bladea)
        Blade::component(Fields::class, 'antibot::fields'); // omogućuje <x-antibot::fields>
        Blade::component(Fields::class, 'antibot-fields');  // omogućuje <x-antibot-fields>

        // publish-anja i middleware ostaju kako jesu...
        $this->publishes([
            __DIR__ . '/../config/antibot.php' => config_path('antibot.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/antibot'),
        ], 'views');

        $this->app['router']->aliasMiddleware('antibot', \FerProjekt\AntiBot\Http\Middleware\VerifyAntiBot::class);
    }
}
