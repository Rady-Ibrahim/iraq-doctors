<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageFileController extends Controller
{
    /**
     * Serve public-disk files without requiring php artisan storage:link.
     */
    public function show(Request $request, string $path): StreamedResponse
    {
        $path = $this->sanitizePath($path);

        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            abort(404);
        }

        return $disk->response($path);
    }

    private function sanitizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path) ?? $path;

        $parts = array_filter(explode('/', $path), fn (string $part) => $part !== '' && $part !== '.' && $part !== '..');

        return implode('/', $parts);
    }
}
