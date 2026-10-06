<?php

namespace App\Support;

use Illuminate\Routing\UrlGenerator;

/**
 * Swaps in TrailingSlashRouteUrlGenerator for route()/route-model calls, and
 * overrides to() directly for url()/url()->current() — the two call chains
 * are structurally different inside the framework (see
 * TrailingSlashRouteUrlGenerator's docblock for the route-side trims), so
 * each needs its own fix rather than one shared override.
 *
 * Bound in place of the 'url' singleton in AppServiceProvider::register().
 */
class TrailingSlashUrlGenerator extends UrlGenerator
{
    protected function routeUrl()
    {
        if (! $this->routeGenerator) {
            $this->routeGenerator = new TrailingSlashRouteUrlGenerator($this, $this->request);
        }

        return $this->routeGenerator;
    }

    /** Paths that must never get a trailing slash appended. */
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

    public function to($path, $extra = [], $secure = null)
    {
        // External/absolute URLs, mailto:, tel:, #fragment-only — the parent
        // returns these untouched via isValidUrl(); this app never wants a
        // slash appended to someone else's domain, so the same check is
        // applied here before doing anything else.
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $uri = parent::to($path, $extra, $secure);

        // Unlike the route-URL path, to()'s query string is appended as a
        // plain string suffix after format() already ran (see to()'s own
        // source: `return $this->format(...).$query;`), so it is already
        // isolated — no need to hunt for "?" inside the result here.
        [$base, $query] = $this->splitQuery($uri);

        $routePath = parse_url($base, PHP_URL_PATH);

        // Same root-collapse as the route-URL side: url('/') comes back as
        // just "http://host" with no path component at all (format()'s
        // trim($root.$path, '/') eats it), so that one case needs the slash
        // even though a null/empty path everywhere else means "exempt".
        $isRoot = ltrim((string) $path, '/') === '' && ($routePath === null || $routePath === '');

        if (! $isRoot && ($routePath === null || $routePath === '' || $routePath === '/'
                || str_ends_with($routePath, '/') || $this->isExempt($routePath))) {
            return $uri;
        }

        return $base.'/'.$query;
    }

    public function current()
    {
        return $this->to($this->request->getPathInfo());
    }

    /** @return array{0: string, 1: string} */
    protected function splitQuery(string $uri): array
    {
        $pos = strpos($uri, '?');

        return $pos === false ? [$uri, ''] : [substr($uri, 0, $pos), substr($uri, $pos)];
    }
}
