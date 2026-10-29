<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Apply the language stored in the settings table to the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Setting::query()->where('key', 'locale')->value('value');
        $supported = (array) config('app.supported_locales', []);

        if (is_string($locale) && array_key_exists($locale, $supported)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
