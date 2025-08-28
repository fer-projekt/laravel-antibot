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

        // Registruj Blade komponente (pokušaj ponovo)
        try {
            Blade::componentNamespace('FerProjekt\\AntiBot\\View\\Components', 'antibot');
            Blade::component(Fields::class, 'antibot-fields');
        } catch (\Exception $e) {
            // Ako ne radi, ignoriši
        }

        // Direktiva 1: @antibot('contact')
        Blade::directive('antibot', function ($expression) {
            return "<?php echo \\FerProjekt\\AntiBot\\antibot_markup({$expression}); ?>";
        });

        // Direktiva 2: @antibotFields('contact') 
        Blade::directive('antibotFields', function ($expression) {
            return "<?php echo \\FerProjekt\\AntiBot\\antibot_markup({$expression}); ?>";
        });

        // Direktiva 3: @antibotInclude('contact')
        Blade::directive('antibotInclude', function ($expression) {
            return "<?php echo view('antibot::fields', \\FerProjekt\\AntiBot\\antibot_data({$expression}))->render(); ?>";
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
