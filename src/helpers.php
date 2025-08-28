<?php

use Illuminate\Http\Request;
use FerProjekt\AntiBot\AntiBot;

if (!function_exists('antibot_verify')) {
    /**
     * Helper funkcija: antibot_verify($request, 'contact');
     */
    function antibot_verify(Request $request, string $formId, ?int $minSeconds = null, ?int $maxSeconds = null): void
    {
        AntiBot::check($request, $formId, $minSeconds, $maxSeconds);
    }
}
