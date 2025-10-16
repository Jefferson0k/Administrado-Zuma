<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicS3ImageController extends Controller
{
    public function show(Request $request, string $path)
    {
        $disk = Storage::disk('s3');

        // Normalize & basic safeguards
        $key = ltrim(str_replace('\\', '/', $path), '/');

        // ✅ Whitelist: allow ONLY blog images folder(s)
        //    Adjust prefixes if you store somewhere else
        $allowedPrefixes = [
            'images/',         // e.g. images/<hash>.png  (your current usage)
            'posts/',          // optional, if you use posts/<id>/...
            'investors/blog/', // optional, if you add a dedicated folder
        ];
        $isAllowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($key, $prefix)) {
                $isAllowed = true;
                break;
            }
        }
        abort_unless($isAllowed, 403, 'Not allowed');

        // File must exist
        abort_unless($disk->exists($key), 404, 'Not found');

        // Only serve common image mime types (extra safety)
        $mime = $disk->mimeType($key) ?: 'application/octet-stream';
        $allowedMimes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif', 'image/svg+xml'];
        abort_unless(in_array($mime, $allowedMimes, true), 403, 'Mime not allowed');

        // Strong caching (tune as needed)
        $headers = [
            'Content-Type'        => $mime,
            'Cache-Control'       => 'public, max-age=86400, s-maxage=86400, immutable',
            'Access-Control-Allow-Origin' => '*', // optional CORS
        ];

        // Stream from S3
        // (Storage::response sets correct headers and streams efficiently)
        return $disk->response($key, basename($key), $headers);
    }
}
