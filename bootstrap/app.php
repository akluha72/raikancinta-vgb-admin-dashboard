<?php

use App\Http\Middleware\VerifyGalleryToken;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias the gallery token guard so routes can reference it concisely.
        $middleware->alias([
            'gallery.token' => VerifyGalleryToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every API failure returns the same JSON envelope — never an HTML
        // stack trace, never a silent empty response. Web routes are untouched.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null; // fall through to the default (web) renderer
            }

            return match (true) {
                $e instanceof ValidationException => response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422),

                $e instanceof AuthenticationException => response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401),

                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404),

                $e instanceof ThrottleRequestsException => response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please slow down and try again shortly.',
                ], 429),

                // Any other HTTP exception keeps its own status + message.
                $e instanceof HttpExceptionInterface => response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Request failed',
                ], $e->getStatusCode()),

                // Truly unexpected: report it, but still answer with structured JSON.
                default => response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again.',
                ], 500),
            };
        });
    })->create();
