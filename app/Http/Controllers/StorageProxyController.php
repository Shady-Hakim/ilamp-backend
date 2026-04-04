<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageProxyController extends Controller
{
    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        // Prevent directory traversal
        $safePath = preg_replace('#\.\.+#', '', str_replace('\\', '/', $path));
        $safePath = ltrim($safePath, '/');

        $fullPath = storage_path('app/public/' . $safePath);

        if (! is_file($fullPath) || ! is_readable($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    }
}
