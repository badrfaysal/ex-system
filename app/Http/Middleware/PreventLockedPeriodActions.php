<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventLockedPeriodActions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // نتحقق إذا كان المسار يستهدف عرض شاشة التعديل أو تنفيذ التعديل أو الحذف
        $method = $request->route() ? $request->route()->getActionMethod() : null;
        if (in_array($method, ['edit', 'update', 'destroy'])) {
            foreach ($request->route()->parameters() as $parameter) {
                // لو المتغير عبارة عن موديل وفيه الـ trait اللي بيعمل تشيك على الفترة المقفولة
                if (is_object($parameter) && method_exists($parameter, 'assertPeriodNotLocked')) {
                    $parameter->assertPeriodNotLocked();
                }
            }
        }

        return $next($request);
    }
}
