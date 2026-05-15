<?php

namespace App\Http\Middleware;

use App\Support\PlatformAudit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class AuditHttpMutations
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        '_token',
        'api_key',
        'secret',
        'client_secret',
        'webhook_secret',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldAudit($request, $response)) {
            PlatformAudit::activity(
                'http_mutation',
                $request->route()?->getName() ?? $request->path(),
                null,
                [
                    'method' => $request->method(),
                    'path' => '/' . ltrim($request->path(), '/'),
                    'route' => $request->route()?->getName(),
                    'status' => $response->getStatusCode(),
                    'input' => $this->sanitizedInput($request),
                ],
                $request,
            );
        }

        return $response;
    }

    private function shouldAudit(Request $request, Response $response): bool
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        if ($response->getStatusCode() >= 500) {
            return false;
        }

        return $request->is('admin/*') || $request->is('partner/*') || $request->is('api/partner/*');
    }

    private function sanitizedInput(Request $request): array
    {
        $input = Arr::except($request->except(array_merge(self::SENSITIVE_KEYS, ['_method'])), self::SENSITIVE_KEYS);

        return collect($input)->map(function (mixed $value, string $key) {
            foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
                if (str_contains(strtolower($key), $sensitiveKey)) {
                    return '[masked]';
                }
            }

            return is_string($value) && mb_strlen($value) > 240
                ? mb_substr($value, 0, 240) . '...'
                : $value;
        })->all();
    }
}
