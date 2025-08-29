<?php

namespace FerProjekt\AntiBot;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;

class AntiBot
{
    /**
     * Server-side provjera anti-bot polja.
     */
    public static function check(Request $request, ?string $expectedFormId = null, ?int $minSeconds = null, ?int $maxSeconds = null): void
    {
        $min = $minSeconds ?? (int) config('antibot.min_seconds', 3);
        $max = $maxSeconds ?? (int) config('antibot.max_seconds', 7200);

        $formId = (string) $request->input('_ab_form', '');
        $ts     = (int) $request->input('_ab_ts', 0);
        $sig    = (string) $request->input('_ab_sig', '');

        // 0) Rate limiting check
        static::checkRateLimit($request);

        // 1) Form ID mora odgovarati (skip ako je expectedFormId null = auto mode)
        if ($expectedFormId !== null && $formId !== $expectedFormId) {
            throw ValidationException::withMessages(['form' => antibot_trans('form_invalid')]);
        }

        $prefix = (string) config('antibot.honeypot_prefix', '_hp_');
        $hpFilled = collect($request->all())->filter(function ($v, $k) use ($prefix) {
            return is_string($k) && strpos($k, $prefix) === 0 && !empty($v);
        })->isNotEmpty();

        if ($hpFilled) {
            throw ValidationException::withMessages(['bot' => antibot_trans('bot_detected')]);
        }

        // 3) Vrijeme
        $elapsed = time() - $ts;
        if ($elapsed < $min) {
            throw ValidationException::withMessages(['speed' => antibot_trans('too_fast')]);
        }
        if ($elapsed > $max) {
            throw ValidationException::withMessages(['expired' => antibot_trans('expired')]);
        }

        // 4) Potpis (HMAC)
        $data = session()->getId() . '|' . $formId . '|' . $ts;
        if (config('antibot.include_ip_in_signature', false)) {
            $data .= '|' . $request->ip();
        }

        $key = (string) config('app.key');
        if (strpos($key, 'base64:') === 0) {
            $key = (string) base64_decode(substr($key, 7));
        }

        $expected = hash_hmac('sha256', $data, $key);
        if (!hash_equals($expected, $sig)) {
            throw ValidationException::withMessages(['signature' => antibot_trans('invalid_signature')]);
        }

        // 5) JavaScript detection
        static::checkJavaScript($request);

        // CSRF rješava VerifyCsrfToken middleware — ne diramo.
    }

    /**
     * Rate limiting provjera
     */
    private static function checkRateLimit(Request $request): void
    {
        $maxAttempts = (int) config('antibot.max_attempts_per_hour', 20);
        if ($maxAttempts <= 0) return; // Disabled

        $ip = $request->ip();
        $key = "antibot_rate_limit:{$ip}";
        
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            throw ValidationException::withMessages(['rate_limit' => antibot_trans('rate_limit')]);
        }

        // Increment counter
        Cache::put($key, $attempts + 1, 3600); // 1 hour
    }

    /**
     * JavaScript detection provjera
     */
    private static function checkJavaScript(Request $request): void
    {
        if (!config('antibot.require_javascript', true)) return; // Disabled

        $jsEnabled = (string) $request->input('_ab_js', '');
        $jsTimestamp = (int) $request->input('_ab_js_ts', 0);
        $jsScreen = (string) $request->input('_ab_screen', '');

        // JavaScript mora biti omogućen
        if ($jsEnabled !== '1') {
            throw ValidationException::withMessages(['javascript' => antibot_trans('javascript_required')]);
        }

        // JavaScript timestamp mora biti valjan
        $jsMaxAge = (int) config('antibot.js_max_age', 3600);
        $jsAge = time() - ($jsTimestamp / 1000); // JS koristi milisekunde
        
        if ($jsAge < 0 || $jsAge > $jsMaxAge) {
            throw ValidationException::withMessages(['javascript' => antibot_trans('javascript_invalid')]);
        }

        // Screen info mora postojati
        if (empty($jsScreen) || !str_contains($jsScreen, 'x') || !str_contains($jsScreen, '|')) {
            throw ValidationException::withMessages(['javascript' => antibot_trans('browser_data_missing')]);
        }
    }
}
