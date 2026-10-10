<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Citizen\DocumentController;
use App\Http\Controllers\Staff\DocumentReviewController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\Admin\CommandCenterController;
use App\Http\Controllers\Citizen\PaymentController;
use App\Http\Controllers\Sandbox\CheckoutController;
use App\Http\Controllers\Treasury\LedgerController;
use App\Http\Controllers\Citizen\EmergencyController;
use App\Http\Controllers\Responder\DispatchController;
use App\Http\Controllers\Citizen\MarketWatchController;
use App\Http\Controllers\Staff\MarketPriceController;
use App\Http\Controllers\Admin\PollManagementController;
use App\Http\Controllers\Citizen\PollController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Staff\ResidentController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/privacy', fn () => Inertia::render('Privacy', [
    'version' => config('nagasync.privacy_notice_version'),
]))->name('privacy');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
     Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{documentRequest}/qr', [DocumentController::class, 'qr'])->name('documents.qr');
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/polls', [PollController::class, 'index'])->name('polls.index');
    Route::post('/polls/{poll}/vote', [PollController::class, 'vote'])->middleware('throttle:10,1')->name('polls.vote');
    Route::get('/emergency', [EmergencyController::class, 'index'])->name('emergency.index');
    Route::post('/emergency', [EmergencyController::class, 'store'])->middleware('throttle:3,1')->name('emergency.store');

    if (! app()->isProduction()) {
        Route::get('/payments/sandbox/{payment:reference_no}', [CheckoutController::class, 'show'])->name('payments.sandbox');
        Route::post('/payments/sandbox/{payment:reference_no}', [CheckoutController::class, 'complete'])->name('payments.sandbox.complete');
    }
});

Route::middleware('auth')->get('/market', [MarketWatchController::class, 'index'])->name('market.index');

Route::get('/verify/{documentRequest:tracking_number}', [VerificationController::class, 'show'])
    ->middleware(['signed', 'throttle:30,1'])->name('documents.verify');

    Route::middleware(['auth', 'role:barangay_staff,admin'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/documents', [DocumentReviewController::class, 'index'])->name('documents.index');
    Route::patch('/documents/{documentRequest}', [DocumentReviewController::class, 'update'])->name('documents.update');
    Route::get('/market-prices', [MarketPriceController::class, 'index'])->name('market.index');
    Route::post('/market-prices', [MarketPriceController::class, 'store'])->name('market.store');
    Route::get('/residents', [ResidentController::class, 'index'])->name('residents.index');
    Route::patch('/residents/{resident}', [ResidentController::class, 'update'])->name('residents.update');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/command-center', [CommandCenterController::class, 'index'])->name('command-center');
    Route::get('/polls', [PollManagementController::class, 'index'])->name('polls.index');
    Route::post('/polls', [PollManagementController::class, 'store'])->name('polls.store');
    Route::patch('/polls/{poll}/publish', [PollManagementController::class, 'publish'])->name('polls.publish');
});
Route::middleware(['auth', 'role:treasury,admin'])->prefix('treasury')->name('treasury.')->group(function () {
    Route::get('/payments', [LedgerController::class, 'index'])->name('payments');
});
Route::middleware(['auth', 'role:mdrrmo,admin'])->prefix('dispatch')->name('dispatch.')->group(function () {
    Route::get('/', [DispatchController::class, 'index'])->name('index');
    Route::patch('/{emergencyAlert}', [DispatchController::class, 'update'])->name('update');
});
require __DIR__.'/auth.php';
