<?php

namespace FerProjekt\AntiBot;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use FerProjekt\AntiBot\View\Components\Fields;

class AntiBotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/antibot.php', 'antibot');
    }


    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'antibot');

        // Jednostavan pristup - samo direktive
        Blade::directive('antibot', function ($expression) {
            return "<?php echo \\FerProjekt\\AntiBot\\antibot_markup({$expression}); ?>";
        });

        // Direktiva za fields
        Blade::directive('antibotFields', function ($expression) {
            return "<?php echo \\FerProjekt\\AntiBot\\antibot_markup({$expression}); ?>";
        });

        // Publishes + middleware alias kao i prije
        $this->publishes([
            __DIR__ . '/../config/antibot.php' => config_path('antibot.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/antibot'),
        ], 'views');

        $this->app['router']->aliasMiddleware(
            'antibot',
            \FerProjekt\AntiBot\Http\Middleware\VerifyAntiBot::class
        );
    }
}
