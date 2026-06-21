<?php

/**
 * Routes principales du site.
 *
 * Le slug historique `guide` est conserve pour compatibilite,
 * mais il correspond bien a la rubrique "Cours".
 */

use App\Http\Controllers\ActionController;
use App\Http\Controllers\CoursDocumentController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\MediaAssetController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ResetPasswordController;
use Illuminate\Support\Facades\Route;

$pagesRoutables = implode('|', [
    'accueil',
    'guide',
    'cours-livrets',
    'cours-livret-a',
    'cours-livret-b',
    'cours-livret-c',
    'cours-livret-d',
    'cours-livret-e',
    'cours-seances',
    'cours-progression',
    'cours-methodologie',
    'cours-strategie',
    'mediatheque',
    'articles',
    'boutique',
    'club',
    'activites',
    'contact',
    'profil',
    'parametres',
    'admin',
]);

Route::get('/fichiers/medias/{nomFichier}', [MediaAssetController::class, 'showPublication'])
    ->where('nomFichier', '[A-Za-z0-9._-]+')
    ->name('fichiers.medias.show');

Route::get('/fichiers/articles/{nomFichier}', [MediaAssetController::class, 'showArticle'])
    ->where('nomFichier', '[A-Za-z0-9._-]+')
    ->name('fichiers.articles.show');

Route::get('/fichiers/cours/{nomFichier}', [CoursDocumentController::class, 'show'])
    ->where('nomFichier', '[A-Za-z0-9._-]+')
    ->name('fichiers.cours.show');

Route::get('/newsletter/desabonnement/{jeton}', [NewsletterController::class, 'unsubscribe'])
    ->where('jeton', '[A-Fa-f0-9]{64}')
    ->name('newsletter.unsubscribe');

Route::get('/', [PageController::class, 'show'])->name('accueil');
Route::post('/', [ActionController::class, 'handle']);

Route::get('/mot-de-passe/oublie', [ForgotPasswordController::class, 'create'])->name('password.request');
Route::post('/mot-de-passe/oublie', [ForgotPasswordController::class, 'store'])->name('password.email');
Route::get('/mot-de-passe/reinitialiser/{token}', [ResetPasswordController::class, 'edit'])->name('password.reset');
Route::post('/mot-de-passe/reinitialiser', [ResetPasswordController::class, 'update'])->name('password.update');

Route::get('/merch', fn () => redirect()->route('boutique'))->name('merch');

Route::get('/{page}', [PageController::class, 'show'])
    ->where('page', $pagesRoutables)
    ->name('page.show');

Route::post('/{page}', [ActionController::class, 'handle'])
    ->where('page', $pagesRoutables);
