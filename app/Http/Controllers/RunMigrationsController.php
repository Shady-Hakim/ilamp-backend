<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * TEMPORARY — delete this file and its route in routes/web.php after use.
 */
class RunMigrationsController extends Controller
{
    private const TOKEN = '08b6610f7f631fca33e1cce80aee6b23689bfbdd23ace277';

    public function __invoke(Request $request): string
    {
        if (! hash_equals(self::TOKEN, (string) $request->query('token', ''))) {
            abort(404);
        }

        $output = [];

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output[] = '=== migrate ===' . PHP_EOL . Artisan::output();

            Artisan::call('cache:clear');
            $output[] = '=== cache:clear ===' . PHP_EOL . Artisan::output();

            Artisan::call('optimize:clear');
            $output[] = '=== optimize:clear ===' . PHP_EOL . Artisan::output();
        } catch (\Throwable $e) {
            $output[] = PHP_EOL . '=== EXCEPTION ===' . PHP_EOL . get_class($e) . ': ' . $e->getMessage();
            $output[] = 'File: ' . $e->getFile() . ':' . $e->getLine();
        }

        return '<pre>' . implode(PHP_EOL, $output) . '</pre>';
    }
}
