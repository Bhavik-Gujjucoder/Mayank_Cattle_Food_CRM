<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Routes that must not be hit during automated smoke tests.
 *
 * @return list<string>
 */
function smokeExcludedRouteNames(): array
{
    return [
        'verification.verify',
        'password.reset',
        'sanctum.csrf-cookie',
    ];
}

/**
 * Resource modules that register a `create` route but use modal forms on index instead.
 *
 * @return list<string>
 */
function smokeOrphanCreateRouteNames(): array
{
    return [
        'brand.create',
        'state.create',
        'city.create',
        'supplier-broker.create',
        'supplier.create',
        'product.create',
        'truck.create',
    ];
}

/**
 * Route names that return file downloads rather than HTML/JSON pages.
 *
 * @return list<string>
 */
function smokeExcludedDownloadRoutePatterns(): array
{
    return [
        'export',
        'download',
    ];
}

/**
 * URI patterns that are unsafe or impractical for smoke GET requests.
 *
 * @return list<string>
 */
function smokeExcludedUriPatterns(): array
{
    return [
        'clear',
        'reset-password/{token}',
        'verify-email/{id}/{hash}',
    ];
}

/**
 * @return list<\Illuminate\Routing\Route>
 */
function smokeGetRoutesWithoutParameters(): array
{
    return collect(Route::getRoutes())
        ->filter(fn ($route) => in_array('GET', $route->methods(), true))
        ->filter(fn ($route) => $route->getName() && ! in_array($route->getName(), smokeExcludedRouteNames(), true))
        ->reject(fn ($route) => in_array($route->getName(), smokeOrphanCreateRouteNames(), true))
        ->reject(fn ($route) => collect(smokeExcludedDownloadRoutePatterns())->contains(
            fn (string $pattern) => str_contains((string) $route->getName(), $pattern)
                || str_contains($route->uri(), $pattern)
        ))
        ->filter(fn ($route) => ! str_contains($route->uri(), '{'))
        ->reject(fn ($route) => collect(smokeExcludedUriPatterns())->contains(
            fn (string $pattern) => $route->uri() === $pattern || str_ends_with($route->uri(), $pattern)
        ))
        ->values()
        ->all();
}

describe('guest public pages', function () {
    it('renders login without a server error', function () {
        get('/login')->assertOk();
    });

    it('renders registration without a server error', function () {
        get('/register')->assertOk();
    });

    it('renders forgot password without a server error', function () {
        get('/forgot-password')->assertOk();
    });

    it('renders OTP verification form without a server error', function () {
        get('/verify-otp')->assertOk();
    });

    it('redirects home to login for guests', function () {
        get('/')->assertRedirect(route('login'));
    });
});

describe('parameter-free GET route smoke', function () {
    beforeEach(function () {
        foreach (['transporter', 'dealer', 'broker', 'staff', 'admin', 'super admin'] as $roleName) {
            \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }
    });

    it('super admin can load app GET routes without server errors', function () {
        $user = superAdminUser();
        $failures = [];

        foreach (smokeGetRoutesWithoutParameters() as $route) {
            $uri = '/'.ltrim($route->uri(), '/');
            actingAs($user);
            $response = test()->get($uri);
            $status = $response->getStatusCode();

            if ($status >= 500) {
                $failures[] = ($route->getName() ?? $uri).' ['.$uri.'] => HTTP '.$status;
            }
        }

        expect($failures)->toBeEmpty("Server errors on GET routes:\n".implode("\n", $failures));
    });

    it('admin can access permissions and roles index without server errors', function () {
        actingAs(adminUser())
            ->get(route('permissions.index'))
            ->assertSuccessful();

        actingAs(adminUser())
            ->get(route('roles.index'))
            ->assertSuccessful();
    });

    it('super admin can access system backup index without server errors', function () {
        actingAs(superAdminUser())
            ->get(route('system.backup.index'))
            ->assertOk()
            ->assertViewIs('system.backup.index');
    });
});

describe('authenticated app pages render', function () {
    it('dashboard renders for verified admin', function () {
        actingAs(adminUser())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard');
    });

    it('general settings page renders for verified user', function () {
        actingAs(authUser())
            ->get(route('generalsetting.create'))
            ->assertOk();
    });

    it('profile page renders for authenticated user', function () {
        $user = authUser();

        actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk();
    });
});
