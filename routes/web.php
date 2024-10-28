<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VoteController;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (\Sanity\Client $sanity, Request $request) {
    $type = $request->has('pickyeaters') ? 'person' : 'toy';

    $results = $sanity->fetch('*[_type=="' . $type . '" && defined(image)]{_id,name,"image":image{asset->{url}},rating}');

    return Inertia::render('Welcome', [
        'initialToys' => $results
    ]);
});

Route::any('/vote', [VoteController::class, 'store']);
