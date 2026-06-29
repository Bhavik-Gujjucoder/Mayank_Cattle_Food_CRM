<?php

use Illuminate\Support\Facades\Route;

/**
 * Collect every named route registered in the application.
 *
 * @return array<string, true>
 */
function registeredRouteNames(): array
{
    return collect(Route::getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter()
        ->flip()
        ->all();
}

/**
 * @return list<string>
 */
function projectPhpFilesForRouteScan(): array
{
    $paths = [
        resource_path('views'),
        app_path('Http/Controllers'),
        app_path('Support'),
    ];

    $files = [];

    foreach ($paths as $basePath) {
        if (! is_dir($basePath)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $pathname = $file->getPathname();

            if (str_ends_with($pathname, '.blade.php') || str_ends_with($pathname, '.php')) {
                $files[] = $pathname;
            }
        }
    }

    sort($files);

    return $files;
}

it('every route() name used in views and app code is registered', function () {
    $registered = registeredRouteNames();
    $missing = [];

    foreach (projectPhpFilesForRouteScan() as $pathname) {
        $content = file_get_contents($pathname);
        preg_match_all("/route\\(['\"]([^'\"]+)['\"]/", $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as $index => [$routeName, $offset]) {
            $routeCallOffset = $matches[0][$index][1];

            if ($routeCallOffset > 0 && substr($content, $routeCallOffset - 2, 2) === '->') {
                continue;
            }

            if (! isset($registered[$routeName])) {
                $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $pathname);
                $missing[$routeName][$relative] = true;
            }
        }
    }

    $message = collect($missing)
        ->map(fn (array $files, string $routeName) => $routeName.' => '.implode(', ', array_keys($files)))
        ->implode(PHP_EOL);

    expect($missing)->toBeEmpty("Unregistered route names found:\n".$message);
});
