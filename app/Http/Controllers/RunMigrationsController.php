<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * TEMPORARY — delete this file and its route in routes/web.php after use.
 */
class RunMigrationsController extends Controller
{
    public function __invoke(Request $request): string
    {
        // Token must be set in .env as MIGRATION_TOKEN (never hardcode secrets in source).
        $token = (string) env('MIGRATION_TOKEN', '');

        if ($token === '' || ! hash_equals($token, (string) $request->query('token', ''))) {
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
