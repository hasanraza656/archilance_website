<?php

namespace App\Support;

use Illuminate\Routing\RouteUrlGenerator;

/**
 * Makes every generated public-page URL end in a trailing slash, to match
 * the old WordPress site the SEO migration is matching against.
 *
 * This has to override to(), not a smaller piece in the middle of it. Two
 * things inside RouteUrlGenerator::to() each independently strip a trailing
 * slash before to() returns, confirmed by reading the source and by direct
 * testing — not assumption:
 *   1. UrlGenerator::format() does `$path = '/'.trim($path, '/')`.
 *   2. format() again does `return trim($root.$path, '/')` at the very end.
 * A slash added anywhere before those two lines run (e.g. by overriding the
 * smaller replaceRouteParameters() instead) is simply discarded. to() itself
 * is the first point after both trims where the string is final.
 */
class TrailingSlashRouteUrlGenerator extends RouteUrlGenerator
{
    /** Paths that must never get a trailing slash appended. */
    protected function isExempt(string $path): bool
    {
        // Admin panel and Laravel's own system routes.
        if (preg_match('#^/?(admin(/|$)|up$|storage/)#', $path)) {
            return true;
        }

        // Anything that already looks like a file (asset(), signed storage
        // links, etc.) keeps its extension untouched.
        if (preg_match('/\.[a-z0-9]{2,5}$/i', $path)) {
            return true;
        }

        return false;
    }

    public function to($route, $parameters = [], $absolute = false)
    {
        $uri = parent::to($route, $parameters, $absolute);

        // The home route ("/") loses its path entirely here: format()'s
        // final trim($root.$path, '/') collapses "http://host" + "/" down
        // to just "http://host", so parse_url() sees no path at all rather
        // than "/". route('home') must still come back as ".../" per the
        // migration spec — treat that one case as "needs a slash" up front,
        // everything else is checked against its actual path.
        $path = parse_url($uri, PHP_URL_PATH);
        $isHomeRoute = $route->uri() === '/' && $path === null;

        if (! $isHomeRoute && ($path === null || $path === '' || $path === '/'
                || str_ends_with($path, '/') || $this->isExempt($path))) {
            return $uri;
        }

        // Splice the slash in right after the path, before any ?query or
        // #fragment — addQueryString() (called inside parent::to()) always
        // orders the final string as path, then query, then fragment.
        if (preg_match('/[?#]/', $uri, $match, PREG_OFFSET_CAPTURE)) {
            $cut = $match[0][1];

            return substr($uri, 0, $cut).'/'.substr($uri, $cut);
        }

        return $uri.'/';
    }
}
