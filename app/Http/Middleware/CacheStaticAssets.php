<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheStaticAssets
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $path = $request->getPathInfo();

        // Vite hashed assets — content-hashed so safe to cache forever
        if (preg_match('#^/build/assets/[^/]+-[A-Za-z0-9_-]{8}\.(js|css)$#', $path)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            return $response;
        }

        // Images and fonts — 30 days
        if (preg_match('#\.(webp|png|jpe?g|gif|svg|ico|woff2?|ttf)$#i', $path)) {
            $response->headers->set('Cache-Control', 'public, max-age=2592000');
            return $response;
        }

        return $response;
    }
}
