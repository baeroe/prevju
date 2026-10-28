<?php

namespace App\Http\Api;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/** Every API error as RFC 9457 problem details: {type, title, status, detail}, plus violations on 422. */
class Problem
{
    public static function from(Throwable $e): JsonResponse
    {
        [$status, $detail, $headers] = match (true) {
            $e instanceof ValidationException => [422, 'The request data is invalid.', []],
            $e instanceof AuthenticationException => [401, 'Missing or invalid bearer token.', []],
            $e instanceof ModelNotFoundException => [404, 'Not found.', []],
            $e instanceof HttpExceptionInterface => [$e->getStatusCode(), $e->getMessage() ?: null, $e->getHeaders()],
            default => [500, config('app.debug') ? $e->getMessage() : null, []],
        };

        $body = array_filter([
            'type' => 'about:blank',
            'title' => Response::$statusTexts[$status] ?? 'Error',
            'status' => $status,
            'detail' => $detail,
        ], fn ($v) => $v !== null);

        if ($e instanceof ValidationException) {
            $body['violations'] = collect($e->errors())
                ->flatMap(fn (array $messages, string $path) => array_map(fn ($message) => ['path' => $path, 'message' => $message], $messages))
                ->values();
        }

        return new JsonResponse($body, $status, [...$headers, 'Content-Type' => 'application/problem+json']);
    }
}
