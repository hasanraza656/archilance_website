<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Incoming-request half of the trailing-slash policy. URL *generation*
 * already appends the slash everywhere (see App\Support\TrailingSlashUrlGenerator);
 * this catches a request that arrives without one and 301s it to the slash
 * version, so the same old-WordPress URL never serves two different paths.
 */
class EnsureTrailingSlash
{
    /** Paths that must never get a trailing-slash redirect. */
    protected function isExempt(string $path): bool
    {
        if (preg_match('#^/?(admin(/|$)|up$|storage/)#', $path)) {
            return true;
        }

        if (preg_match('/\.[a-z0-9]{2,5}$/i', $path)) {
            return true;
        }

        return false;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = $request->getPathInfo();

        if ($path === '' || $path === '/' || str_ends_with($path, '/') || $this->isExempt($path)) {
            return $next($request);
        }

        $query = $request->getQueryString();
        $target = $path . '/' . ($query ? '?' . $query : '');

        return redirect($target, 301);
    }
}
