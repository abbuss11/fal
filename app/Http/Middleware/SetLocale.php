<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.supported_locales', ['fr', 'en']);
        $configuredLocale = config('app.locale', 'fr');
        $sessionLocale = $request->session()->get('locale');

        $locale = in_array($sessionLocale, $supportedLocales, true)
            ? $sessionLocale
            : $configuredLocale;

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
