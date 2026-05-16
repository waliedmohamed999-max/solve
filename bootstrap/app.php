<?php

use App\Http\Middleware\RequireAdminAuth;
use App\Http\Middleware\RequirePartnerAuth;
use App\Http\Middleware\AuditHttpMutations;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;

$basePath = dirname(__DIR__);

$app = Application::configure(basePath: $basePath)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            AuditHttpMutations::class,
        ]);

        $middleware->alias([
            'admin.auth' => RequireAdminAuth::class,
            'partner.auth' => RequirePartnerAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $expiredSession = 'انتهت الجلسة. افتح صفحة الدخول مرة أخرى وسجّل الدخول.';

        $exceptions->render(function (TokenMismatchException $exception, Request $request) use ($expiredSession) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return redirect()
                    ->route('admin.login')
                    ->withErrors(['username' => $expiredSession]);
            }

            if ($request->is('partner') || $request->is('partner/*')) {
                return redirect()
                    ->route('partner.login')
                    ->withErrors(['username' => $expiredSession]);
            }

            return null;
        });

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) use ($expiredSession) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            if ($request->is('admin') || $request->is('admin/*')) {
                return redirect()
                    ->route('admin.login')
                    ->withErrors(['username' => $expiredSession]);
            }

            if ($request->is('partner') || $request->is('partner/*')) {
                return redirect()
                    ->route('partner.login')
                    ->withErrors(['username' => $expiredSession]);
            }

            return $response;
        });
    })->create();

if ($storagePath = env('LARAVEL_STORAGE_PATH') ?: env('APP_STORAGE_PATH')) {
    $app->useStoragePath($storagePath);
}

return $app;
