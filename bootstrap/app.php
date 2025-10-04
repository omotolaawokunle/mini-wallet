<?php

use App\Services\ResponseService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    api: __DIR__ . '/../routes/api.php',
    channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    // Handle validation exceptions
    $exceptions->render(function (ValidationException $e, Request $request) {
        if ($request->expectsJson()) {
            return ResponseService::validationError($e);
        }
    });

    // Handle not found exceptions
    $exceptions->render(function (NotFoundHttpException $e, Request $request) {
        if ($request->expectsJson()) {
            return ResponseService::notFound(
                $e->getMessage() ?: 'Resource not found'
            );
        }
    });

    // Handle authentication exceptions
    $exceptions->render(function (AuthenticationException $e, Request $request) {
        if ($request->expectsJson()) {
            return ResponseService::unauthorized(
                $e->getMessage() ?: 'Unauthenticated'
            );
        }
    });

    // Handle access denied exceptions
    $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
        if ($request->expectsJson()) {
            return ResponseService::forbidden(
                $e->getMessage() ?: 'Access forbidden'
            );
        }
    });

    // Handle all other exceptions
    $exceptions->render(function (Throwable $e, Request $request) {
        if ($request->expectsJson()) {
            $message = config('app.debug')
                ? $e->getMessage()
                : 'Internal server error';

            $errors = config('app.debug') ? [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->toArray(),
            ] : null;

            return ResponseService::serverError($message, $errors);
        }
    });
    })
    ->withBroadcasting(
        channels: __DIR__ . '/../routes/channels.php',
        attributes: ['prefix' => 'api', 'middleware' => ['auth:sanctum']],
    )->create();
