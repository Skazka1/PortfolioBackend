<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.force_https')) {
            return $next($request);
        }

        if ($this->requestUsesHttps($request)) {
            return $next($request);
        }

        if ($this->isLocalDevHost($request)) {
            return $next($request);
        }

        return redirect()->secure($request->getRequestUri(), 301);
    }

    private function requestUsesHttps(Request $request): bool
    {
        if ($request->secure()) {
            return true;
        }

        return strtolower((string) $request->header('X-Forwarded-Proto')) === 'https';
    }

    private function isLocalDevHost(Request $request): bool
    {
        return in_array($request->getHost(), ['localhost', '127.0.0.1', '[::1]'], true);
    }
}
