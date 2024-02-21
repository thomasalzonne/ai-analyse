<?php

use App\Http\Controllers\DomainController;
use App\Http\Controllers\EmailCampaignController;
use App\Http\Controllers\EmailCampaignScreenshotController;
use App\Http\Controllers\EmailClientController;
use App\Http\Controllers\EmailListController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnalyseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/domains', [DomainController::class, 'index'])->name('domains');
    Route::get('/domains/{domain}', [DomainController::class, 'show'])->name('domain');
    Route::get('/domains/{domain}/email', [EmailClientController::class, 'show'])->name('email.client');
    Route::get('/domains/{domain}/email/lists', [EmailClientController::class, 'lists'])->name('email.lists');
    Route::get('/domains/{domain}/email/lists/{emailList}', [EmailListController::class, 'show'])->name('email.list');
    Route::get('/domains/{domain}/email/campaigns', [EmailClientController::class, 'campaigns'])->name('email.campaigns');
    Route::get('/domains/{domain}/email/campaigns/{emailCampaign}', [EmailCampaignController::class, 'show'])->name('email.campaign');
    Route::get('/domains/{domain}/email/campaigns/{emailCampaign}/screenshot/{device}', [EmailCampaignScreenshotController::class, 'get'])->name('email.campaign.screenshot');
});

Route::post('/analyse', [AnalyseController::class, 'analyse'])->name('analyse');
Route::get('/analyse', [AnalyseController::class, 'show'])->name('analyse');
require __DIR__.'/auth.php';
