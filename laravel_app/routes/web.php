<?php

use App\Http\Controllers\ActionController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'show'])->name('accueil');
Route::post('/', [ActionController::class, 'handle']);

Route::get('/merch', fn () => redirect()->route('boutique'))->name('merch');

Route::get('/{page}', [PageController::class, 'show'])
    ->where('page', 'accueil|guide|mediatheque|articles|boutique|club|activites|contact|profil|parametres|admin')
    ->name('page.show');

Route::post('/{page}', [ActionController::class, 'handle'])
    ->where('page', 'accueil|guide|mediatheque|articles|boutique|club|activites|contact|profil|parametres|admin');
