<?php

return [
    // Minimalno vrijeme (sekunde) između rendera forme i submit-a
    'min_seconds' => 3,

    // Najdulje vrijeme valjanosti potpisa (sekunde)
    'max_seconds' => 7200,

    // Prefiks za honeypot polje (ime je dinamično, ali koristi ovaj prefiks)
    'honeypot_prefix' => '_email_',

    // Po želji veži potpis i na IP (strože, ali pazi na proxy-je/load balancere)
    'include_ip_in_signature' => false,
];
