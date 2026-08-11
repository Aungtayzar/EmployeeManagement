<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class ExportDownloadController extends Controller
{
    public function download(string $filename)
    {
        $disk = Storage::disk('local');
        $path = "exports/$filename";

        if (! $disk->exists($path)) {
            abort(404);
        }

        return $disk->download($path);
    }
}
