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
if (!function_exists('antibot_markup')) {
    /**
     * Vrati kompletni HTML za anti-bot polja (za @antibot('form-id') ili raw echo).
     */
    function antibot_markup(string $form = 'default'): string
    {
        $c = new Fields($form);
        return view('antibot::fields', get_object_vars($c))->render();
    }
}