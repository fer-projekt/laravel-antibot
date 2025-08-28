<?php

namespace FerProjekt\AntiBot\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use FerProjekt\AntiBot\AntiBot;

class VerifyAntiBot
{
    public function handle(Request $request, Closure $next, string $formId = 'default')
    {
        AntiBot::check($request, $formId);
        return $next($request);
    }
}
