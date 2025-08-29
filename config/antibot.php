<?php

return [
    // Minimalno vrijeme (sekunde) između rendera forme i submit-a
    'min_seconds' => 3,

    // Najdulje vrijeme valjanosti potpisa (sekunde)
    'max_seconds' => 7200,

    // Prefiks za honeypot polje (ime je dinamično, ali koristi ovaj prefiks)
    'honeypot_prefix' => '_hp_',

    // Po želji veži potpis i na IP (strože, ali pazi na proxy-je/load balancere)
    'include_ip_in_signature' => false,

    // Rate limiting - maksimalan broj pokušaja po IP-u po satu
    'max_attempts_per_hour' => 20,

    // JavaScript detection - zahtijeva li JavaScript polja
    'require_javascript' => true,

    // JavaScript timeout - najduže vrijeme između JS i server timestampa (sekunde)
    'js_max_age' => 3600,

    // Supported languages for error messages (add new languages here)
    'supported_languages' => ['hr', 'en', 'de'],

    // Default fallback language when detection fails
    'fallback_language' => 'en',
];
