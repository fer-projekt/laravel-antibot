<?php

use Illuminate\Http\Request;
use FerProjekt\AntiBot\AntiBot;
use FerProjekt\AntiBot\View\Components\Fields;

if (!function_exists('antibot_verify')) {
    /**
     * Helper funkcija: antibot_verify($request) ili antibot_verify($request, 'contact')
     */
    function antibot_verify(Request $request, ?string $formId = null, ?int $minSeconds = null, ?int $maxSeconds = null): void
    {
        AntiBot::check($request, $formId, $minSeconds, $maxSeconds);
    }
}

if (!function_exists('antibot_data')) {
    /**
     * Vrati podatke za antibot view (za @include).
     * Ako se ne preda form ID, automatski generiraj unique ID.
     */
    function antibot_data(?string $form = null): array
    {
        if ($form === null) {
            // Automatski generiraj unique ID na osnovu trenutne lokacije
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = $backtrace[0] ?? [];
            $viewCaller = $backtrace[1] ?? [];
            
            // Kombinacija: file path + line number + timestamp za unique ID  
            $uniqueData = ($caller['file'] ?? 'unknown') . ':' . 
                         ($caller['line'] ?? '0') . ':' . 
                         request()->path();
            
            $form = 'auto_' . substr(hash('sha256', $uniqueData), 0, 8);
        }
        
        $c = new Fields($form);
        return get_object_vars($c);
    }
}

if (!function_exists('antibot_trans')) {
    /**
     * Translate antibot messages with locale detection and fallback.
     */
    function antibot_trans(string $key): string
    {
        // Get supported languages from config
        $supportedLocales = (array) config('antibot.supported_languages', ['hr', 'en', 'de']);
        $fallbackLocale = (string) config('antibot.fallback_language', 'en');
        
        // Detect locale: app locale → browser → config fallback
        $locale = app()->getLocale();
        
        // If current locale is not supported, try browser detection
        if (!in_array($locale, $supportedLocales)) {
            $browserLocale = request()->getPreferredLanguage($supportedLocales) ?? $fallbackLocale;
            $locale = $browserLocale;
        }
        
        // Load translation file for antibot package
        $langPath = __DIR__ . "/../resources/lang/{$locale}/antibot.php";
        
        if (file_exists($langPath)) {
            $translations = include $langPath;
            return $translations[$key] ?? $translations['form_invalid'] ?? 'Validation error.';
        }
        
        // Ultimate fallback - load configured fallback language
        $fallbackPath = __DIR__ . "/../resources/lang/{$fallbackLocale}/antibot.php";
        if (file_exists($fallbackPath)) {
            $translations = include $fallbackPath;
            return $translations[$key] ?? 'Validation error.';
        }
        
        return 'Validation error.';
    }
}