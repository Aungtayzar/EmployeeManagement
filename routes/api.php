<?php

use App\Http\Controllers\ExportDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/exports/{filename}', [ExportDownloadController::class, 'download'])
    ->name('download.export')
    ->middleware('signed');
