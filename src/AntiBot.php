<?php

namespace FerProjekt\AntiBot;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AntiBot
{
    /**
     * Server-side provjera anti-bot polja.
     */
    public static function check(Request $request, string $expectedFormId, ?int $minSeconds = null, ?int $maxSeconds = null): void
    {
        $min = $minSeconds ?? (int) config('antibot.min_seconds', 3);
        $max = $maxSeconds ?? (int) config('antibot.max_seconds', 7200);

        $formId = (string) $request->input('_ab_form', '');
        $ts     = (int) $request->input('_ab_ts', 0);
        $sig    = (string) $request->input('_ab_sig', '');

        // 1) Form ID mora odgovarati
        if ($formId !== $expectedFormId) {
            throw ValidationException::withMessages(['form' => 'Neispravan identifikator forme.']);
        }

        $prefix = (string) config('antibot.honeypot_prefix', '_email_');
        $hpFilled = collect($request->all())->filter(function ($v, $k) use ($prefix) {
            return is_string($k) && strpos($k, $prefix) === 0 && !empty($v);
        })->isNotEmpty();

        if ($hpFilled) {
            throw ValidationException::withMessages(['bot' => 'Detektiran bot unos.']);
        }

        // 3) Vrijeme
        $elapsed = time() - $ts;
        if ($elapsed < $min) {
            throw ValidationException::withMessages(['speed' => 'Prebrzo slanje forme.']);
        }
        if ($elapsed > $max) {
            throw ValidationException::withMessages(['expired' => 'Forma je istekla, pokušaj ponovno.']);
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
            throw ValidationException::withMessages(['signature' => 'Neispravan potpis.']);
        }

        // CSRF rješava VerifyCsrfToken middleware — ne diramo.
    }
}
