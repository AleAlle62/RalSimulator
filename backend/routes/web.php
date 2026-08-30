<?php

use Illuminate\Support\Facades\Route;

/**
 * Everything that is not /api or the Filament panel is the SPA's business: Vue Router runs in
 * history mode, so a deep link like /simulazione typed straight into the address bar reaches
 * Laravel first and has to come back as index.html for the router to take over.
 *
 * The `where` guard keeps this from swallowing routes Laravel or Filament own. /api is already
 * outside this file, but /admin and the Sanctum endpoints are not, and a catch-all without it
 * would shadow the whole panel.
 *
 * A 404 here rather than a fallback: until the SPA is built, saying so plainly beats serving a
 * blank page nobody can explain.
 */
Route::get('/{any?}', function () {
    $spa = public_path('index.html');

    abort_unless(file_exists($spa), 404, 'La SPA non è stata ancora compilata: esegui "pnpm build" in frontend/.');

    return file_get_contents($spa);
})->where('any', '^(?!api|admin|sanctum|livewire|storage|up).*$');
