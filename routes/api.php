<?php

use Illuminate\Support\Facades\Route;

Route::get('/exports/{filename}', function ($filename) {
    $path = storage_path('app/exports/' . $filename);

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->download($path);
})->name('download.export');
