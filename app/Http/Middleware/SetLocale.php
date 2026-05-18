<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** @var list<string> */
    public const ALLOWED_LOCALES = ['es', 'en', 'fr', 'de'];

    public function handle(Request $request, Closure $next): Response
    {
        $chosen = $this->resolveLocale($request->header('Accept-Language'));

        if ($chosen !== null) {
            App::setLocale($chosen);
        }

        return $next($request);
    }

    private function resolveLocale(?string $acceptLanguage): ?string
    {
        if ($acceptLanguage === null || $acceptLanguage === '') {
            return null;
        }

        foreach (explode(',', $acceptLanguage) as $lang) {
            $locale = strtolower(trim(explode(';', $lang)[0]));

            if (in_array($locale, self::ALLOWED_LOCALES, true)) {
                return $locale;
            }
        }

        return null;
    }
}
