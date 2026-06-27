<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('storage_public_url')) {
    /**
     * Public URL for a file on the "public" disk (no storage symlink required).
     */
    function storage_public_url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url(ltrim($path, '/'));
    }
}
