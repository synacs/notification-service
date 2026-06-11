<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для идемпотентности запросов.
 *
 * При повторном запросе с тем же Idempotency-Key возвращает кешированный ответ,
 * не выполняя бизнес-алгоритм повторно.
 *
 * - Работает только для POST, PUT, PATCH
 * - Требует заголовок Idempotency-Key
 * - Кеширует успешные ответы на 24 часа
 * - Ключ кеша: idempotency:{METHOD}:{PATH}:{KEY}
 */
class IdempotencyMiddleware
{
    private const int TTL = 86400;

    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');

        if (!$key) {
            return $next($request);
        }

        $cacheKey = 'idempotency:' . $request->method() . ':' . $request->path() . ':' . $key;

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            return response()->json($cached['data'], $cached['status']);
        }

        $response = $next($request);

        if ($response->isSuccessful()) {
            Cache::put($cacheKey, [
                'data' => $response->getData(true),
                'status' => $response->getStatusCode(),
            ], self::TTL);
        }

        return $response;
    }
}
