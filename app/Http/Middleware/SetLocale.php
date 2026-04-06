<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->locale) {
            App::setLocale(auth()->user()->locale);
        }

        return $next($request);
    }
}
