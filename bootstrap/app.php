<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // تطبيق اللغة المختارة على كل صفحات الويب
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // كل خطأ (404 / 403 / 419 / 500 ... إلخ) يظهر بصفحة عربية موحّدة بدل صفحات
        // لارافيل الافتراضية. الأخطاء غير المتوقعة (باجات حقيقية) بتفضل تظهر بتفاصيلها
        // الكاملة في وضع التطوير المحلي (APP_DEBUG=true) عشان التشخيص يفضل ممكن.
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->expectsJson()) {
                return null;
            }

            if ($e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }

            $isHttpException = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

            if (!$isHttpException && config('app.debug')) {
                return null;
            }

            $status = $isHttpException ? $e->getStatusCode() : 500;
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                $status = 404;
            }

            $view = view()->exists("errors.{$status}") ? "errors.{$status}" : 'errors.500';
            $renderStatus = ($status >= 400 && $status < 600) ? $status : 500;

            return response()->view($view, ['exceptionMessage' => $e->getMessage()], $renderStatus);
        });
    })->create();
