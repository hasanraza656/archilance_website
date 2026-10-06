<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // EnsureTrailingSlash must run before ApplyRedirects — a request that
        // gets 301'd to its slash variant here should never also be looked up
        // against the redirects table (which would waste a DB hit on a path
        // that's about to change anyway).
        $middleware->append(\App\Http\Middleware\EnsureTrailingSlash::class);
        $middleware->append(\App\Http\Middleware\ApplyRedirects::class);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);

        // The auth middleware sends guests to a route named `login` by default.
        // This app's sign-in page is `admin.login`, so without this a logged-out
        // visitor to /admin gets "Route [login] not defined" instead of the form.
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        // ...and a signed-in admin who lands on the login page goes to the panel.
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
