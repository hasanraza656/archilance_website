<?php

namespace App\Providers;

use App\Support\TrailingSlashUrlGenerator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Re-bind the 'url' singleton to a subclass that appends a trailing
        // slash to generated route URLs, to match the old WordPress site
        // (see TrailingSlashUrlGenerator for why this needs a subclass
        // rather than a macro). Framework's own RoutingServiceProvider
        // still attaches its session/key resolvers afterwards via
        // Container::extend(), which wraps whatever factory produced the
        // instance — confirmed by reading Container::extend(), which keys
        // purely off the abstract name.
        $this->app->singleton('url', function ($app) {
            $routes = $app['router']->getRoutes();
            $app->instance('routes', $routes);

            return new TrailingSlashUrlGenerator(
                $routes,
                $app->rebinding('request', function ($app, $request) {
                    $app['url']->setRequest($request);
                }),
                $app['config']['app.asset_url']
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The default Tailwind pagination view ships Tailwind utility classes
        // (w-5 h-5, sm:hidden, etc.) that do nothing here — this project has
        // no Tailwind build, so those SVGs rendered at their unconstrained
        // intrinsic size and both the mobile and desktop blocks showed at
        // once. Admin pages call ->links() with no view, so the default
        // covers every admin index; the public blog index passes its own
        // 'vendor.pagination.site' explicitly (see front/blog/index.blade.php)
        // since it needs the dark-theme front-end styling instead.
        Paginator::defaultView('vendor.pagination.admin');
        Paginator::defaultSimpleView('vendor.pagination.admin');
    }
}
