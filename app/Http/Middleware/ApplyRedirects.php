<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Honours the redirect table managed in the admin panel. Only runs when the
 * response is a 404, so a live route is never intercepted.
 */
class ApplyRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404) {
            return $response;
        }

        // Request::path() always strips the trailing slash (confirmed: for
        // /contact/ it returns "contact", not "contact/"), so the candidate
        // list has to add the slash back, not just try removing one that's
        // already gone — this project's redirect rows are stored WITH the
        // trailing slash throughout, per the site's own trailing-slash policy.
        $path = '/' . ltrim($request->path(), '/');
        $withSlash = rtrim($path, '/') . '/';

        $map = Cache::remember('redirects.map', 300, fn () =>
            Redirect::where('is_active', true)->pluck('id', 'from')->all());

        foreach (array_unique([$path, $withSlash]) as $candidate) {
            if (! isset($map[$candidate])) {
                continue;
            }

            $rule = Redirect::find($map[$candidate]);
            if (! $rule) {
                continue;
            }

            $rule->increment('hits');

            if ($rule->status === 410) {
                abort(410);
            }

            return redirect($rule->to, $rule->status);
        }

        return $this->matchPattern($withSlash, $request) ?? $response;
    }

    /**
     * WordPress-era URL patterns that can't live as an exact `from` string in
     * the redirects table — kept in version control (where regex belongs)
     * rather than typed into an admin textarea. The exact-match table above
     * remains the primary, fast path editors actually use day to day.
     *
     * $path always carries a trailing slash here (the caller normalises it,
     * same as the exact-match lookup above) — every pattern below assumes
     * that rather than making the slash optional per-pattern.
     */
    protected function matchPattern(string $path, Request $request): ?Response
    {
        $query = $request->getQueryString();
        $qs = $query ? '?' . $query : '';

        // AMP copies: /about-us/amp/, /amp/, /amp/?utm_... — strip the /amp/
        // segment and redirect to the canonical page, keeping the query string.
        if (preg_match('#^(.*)/amp/$#', $path, $m)) {
            $target = ($m[1] === '' ? '/' : $m[1] . '/');

            return redirect($target . $qs, 301);
        }

        // WordPress RSS feeds: /feed/, /{post}/feed/.
        if (preg_match('#^(.*)/feed/$#', $path)) {
            return redirect('/blogs/', 301);
        }

        // Taxonomy archives: project types, software terms, project categories.
        if (preg_match('#^/(project-type|project-category|softwares-used)/#', $path)) {
            return redirect('/projects/', 301);
        }

        // Old review pages -> homepage testimonials section.
        if (preg_match('#^/review/#', $path)) {
            return redirect('/#testimonials', 301);
        }

        // WordPress date archives: /2025/06/23/, /2025/06/.
        if (preg_match('#^/\d{4}/\d{2}/(\d{2}/)?$#', $path)) {
            return redirect('/blogs/', 301);
        }

        // Old blog pagination: /blogs/page/{N}/ -> /blogs/?page={N}.
        if (preg_match('#^/blogs/page/(\d+)/$#', $path, $m)) {
            return redirect('/blogs/?page=' . $m[1], 301);
        }

        return null;
    }
}
