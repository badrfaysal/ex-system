<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * تطبيق اللغة المحفوظة في الجلسة على كل طلب.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // الافتراضي عربي ما لم يختر المستخدم الإنجليزية ويُحفظ في الجلسة
        $locale = $request->session()->get('locale', 'ar');

        if (in_array($locale, ['ar', 'en'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
