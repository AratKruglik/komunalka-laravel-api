<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ThrottleRequests::class.':api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request): bool {
            return $request->is('api/*') || $request->wantsJson();
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->wantsJson()) {
                return null;
            }

            $timestamp = now()->toIso8601ZuluString();
            $path = '/'.$request->path();

            if ($e instanceof ValidationException) {
                return response()->json([
                    'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Data validation error',
                    'errors' => $e->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'statusCode' => Response::HTTP_UNAUTHORIZED,
                    'message' => $e->getMessage() ?: 'Unauthenticated',
                    'details' => null,
                    'timestamp' => $timestamp,
                    'path' => $path,
                ], Response::HTTP_UNAUTHORIZED);
            }

            if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'statusCode' => Response::HTTP_UNAUTHORIZED,
                    'message' => 'Token has expired.',
                    'details' => null,
                    'timestamp' => $timestamp,
                    'path' => $path,
                ], Response::HTTP_UNAUTHORIZED);
            }

            if ($e instanceof TokenInvalidException) {
                return response()->json([
                    'statusCode' => Response::HTTP_UNAUTHORIZED,
                    'message' => 'Token is invalid.',
                    'details' => null,
                    'timestamp' => $timestamp,
                    'path' => $path,
                ], Response::HTTP_UNAUTHORIZED);
            }

            if ($e instanceof JWTException) {
                return response()->json([
                    'statusCode' => Response::HTTP_UNAUTHORIZED,
                    'message' => 'Token error.',
                    'details' => null,
                    'timestamp' => $timestamp,
                    'path' => $path,
                ], Response::HTTP_UNAUTHORIZED);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'statusCode' => Response::HTTP_FORBIDDEN,
                    'message' => $e->getMessage() ?: 'Forbidden',
                    'details' => null,
                    'timestamp' => $timestamp,
                    'path' => $path,
                ], Response::HTTP_FORBIDDEN);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json([
                    'statusCode' => Response::HTTP_NOT_FOUND,
                    'message' => 'Not found',
                    'details' => null,
                    'timestamp' => $timestamp,
                    'path' => $path,
                ], Response::HTTP_NOT_FOUND);
            }

            if ($e instanceof QueryException) {
                $sqlState = $e->errorInfo[0] ?? null;
                if (in_array($sqlState, ['23505', '23503'])) {
                    return response()->json([
                        'statusCode' => Response::HTTP_CONFLICT,
                        'message' => 'Conflict',
                        'details' => null,
                        'timestamp' => $timestamp,
                        'path' => $path,
                    ], Response::HTTP_CONFLICT);
                }
            }

            if ($e instanceof TooManyRequestsHttpException) {
                return response()->json([
                    'statusCode' => Response::HTTP_TOO_MANY_REQUESTS,
                    'message' => 'Too many requests',
                    'details' => null,
                    'timestamp' => $timestamp,
                    'path' => $path,
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

            if ($e instanceof HttpException) {
                $status = $e->getStatusCode();

                return response()->json([
                    'statusCode' => $status,
                    'message' => $e->getMessage() ?: Response::$statusTexts[$status] ?? 'Error',
                    'details' => null,
                    'timestamp' => $timestamp,
                    'path' => $path,
                ], $status);
            }

            $status = Response::HTTP_INTERNAL_SERVER_ERROR;

            return response()->json([
                'statusCode' => $status,
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'details' => config('app.debug') ? $e->getTraceAsString() : null,
                'timestamp' => $timestamp,
                'path' => $path,
            ], $status);
        });
    })->create();
