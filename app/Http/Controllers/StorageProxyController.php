<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageProxyController extends Controller
{
    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        $base = realpath(storage_path('app/public'));

        if ($base === false) {
            abort(404);
        }

        // Resolve without following symlinks to block traversal through them
        $fullPath = $base . '/' . ltrim(str_replace('\\', '/', $path), '/');
        $resolved = realpath($fullPath);

        // Reject if file doesn't exist, is not within the storage root, or is not a regular file
        if ($resolved === false || ! str_starts_with($resolved, $base . DIRECTORY_SEPARATOR) || ! is_file($resolved)) {
            abort(404);
        }

        return response()->file($resolved);
    }
}
