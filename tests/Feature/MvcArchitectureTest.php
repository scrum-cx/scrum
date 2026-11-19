<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Guards the MVC rules from the CSE470 "MVC Architecture" lecture across the whole project:
 *   Browser → Router → Controller → Model → Database → Model → Controller → View → Browser
 *
 * - Router:     every route points to Controller@action (no closures, no Route::resource).
 * - Controller: no query building; database work is asked of the models.
 * - View:       only displays values it was given; no queries, sessions, auth or permission checks.
 *
 * If one of these fails, move the code to the right layer (see docs/MVC.md).
 */
class MvcArchitectureTest extends TestCase
{
    /** Code that belongs in a model, not in the controller layer. */
    private const QUERY_PATTERNS = [
        '/\bDB::/' => 'DB:: facade',
        '/::where\w*\(/' => 'Model::where...()',
        '/->where\w*\(/' => '->where...()',
        '/::query\(/' => 'Model::query()',
        '/::find(OrFail)?\(/' => 'Model::find()',
        '/::all\(/' => 'Model::all()',
        '/->orderBy\w*\(/' => '->orderBy()',
        '/->paginate\(/' => '->paginate()',
        '/->(withCount|withSum|withAvg|loadCount|load)\(|->with\(\'[\w.]+\'\)/' => 'eager loading / aggregates',
        '/->join\(/' => '->join()',
        '/Rule::(unique|exists)\(/' => 'Rule::unique/exists (ask a model method instead)',
        '/[\'"](unique|exists):/' => "'unique:'/'exists:' rule (ask a model method instead)",
    ];

    /** Things a view must not do itself; the controller passes the result instead. */
    private const VIEW_PATTERNS = [
        '/Auth::/' => 'Auth:: (pass the user data from the controller)',
        '/\bauth\(\)/' => 'auth() (pass the user data from the controller)',
        '/@(auth|guest)\b/' => '@auth/@guest (decide in the controller)',
        '/@(can|cannot|elsecan|canany)\b/' => '@can (pass a $can... flag from the controller)',
        '/\bsession\(/' => 'session() (pass the value from the controller)',
        '/\brequest\(\)|\$request\b/' => 'request (pass the value from the controller)',
        '/Route::has/' => 'Route::has (decide in the controller)',
        '/\binstanceof\b/' => 'instanceof (decide in the controller)',
    ];

    public function test_every_route_points_to_a_controller_action(): void
    {
        $problems = [];

        foreach (['routes/web.php', 'routes/auth.php'] as $file) {
            foreach (file(base_path($file)) as $i => $line) {
                $where = "{$file}:".($i + 1);

                if (preg_match('/Route::(resource|apiResource|view|redirect)\(/', $line)) {
                    $problems[] = "{$where} uses a shortcut route; write METHOD /url → Controller@action explicitly";
                }
                if (preg_match('/Route::(get|post|put|patch|delete|options|any|match)\(/', $line)
                    && ! str_contains($line, 'Controller::class')) {
                    $problems[] = "{$where} route does not point to a controller";
                }
                if (preg_match('/function\s*\(|fn\s*\(/', $line) && ! str_contains($line, '->group(function')) {
                    $problems[] = "{$where} has a closure; handle the request in a controller";
                }
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    public function test_controller_layer_does_not_build_database_queries(): void
    {
        $files = [];
        foreach ([app_path('Http'), app_path('Policies')] as $dir) {
            if (File::isDirectory($dir)) {
                $files = array_merge($files, File::allFiles($dir));
            }
        }

        $this->assertNoPatterns($files, self::QUERY_PATTERNS, 'move this query into a model method');
    }

    public function test_views_only_display_what_they_are_given(): void
    {
        $files = File::allFiles(resource_path('views'));

        $this->assertNoPatterns($files, self::VIEW_PATTERNS + self::QUERY_PATTERNS, 'views only display values passed to them');
    }

    /** @param  \SplFileInfo[]  $files */
    private function assertNoPatterns(array $files, array $patterns, string $advice): void
    {
        $problems = [];

        foreach ($files as $file) {
            foreach (file($file->getPathname()) as $i => $line) {
                if (preg_match('/^\s*(\*|\/\/|\{\{--)/', $line)) {
                    continue;   // ignore comments
                }
                foreach ($patterns as $regex => $label) {
                    if (preg_match($regex, $line)) {
                        $path = str_replace(base_path().'/', '', $file->getPathname());
                        $problems[] = "{$path}:".($i + 1)." uses {$label}";
                    }
                }
            }
        }

        $this->assertSame([], $problems, "MVC rule broken ({$advice}):\n".implode("\n", $problems));
    }
}
