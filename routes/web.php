<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function() {
    return response()->json([
        'success' => false,
        'data' => 'Website moved',
    ]);
});

Route::get('/download/{document}', function($document) {
    $headers = [
        'Content-Type' => 'application/pdf',
    ];
    return response()->download(public_path("documents" . DIRECTORY_SEPARATOR . $document . ".pdf"), null, $headers);
})->name('document');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
// write echo
