<?php

use App\Http\Controllers\Admin\CommandCenterController;
use App\Http\Controllers\Admin\PollManagementController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Citizen\DocumentController;
use App\Http\Controllers\Citizen\EmergencyController;
use App\Http\Controllers\Citizen\MarketWatchController;
use App\Http\Controllers\Citizen\PaymentController;
use App\Http\Controllers\Citizen\PollController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Directory\ExploreController;
use App\Http\Controllers\Directory\MyBusinessController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Responder\DispatchController;
use App\Http\Controllers\Sandbox\CheckoutController;
use App\Http\Controllers\Staff\BusinessReviewController;
use App\Http\Controllers\Staff\CounterController;
use App\Http\Controllers\Staff\DocumentReviewController;
use App\Http\Controllers\Staff\FeeAssessmentController;
use App\Http\Controllers\Staff\MarketPriceController;
use App\Http\Controllers\Staff\RequirementReviewController;
use App\Http\Controllers\Staff\ResidentController;
use App\Http\Controllers\Treasury\LedgerController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
*/

// The welcome page is the first page visitors see
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

Route::get('/privacy', fn () => Inertia::render('Privacy', [
    'version' => config('nagasync.privacy_notice_version'),
]))->name('privacy');

// Explore Market: public business directory (citizens and barangay staff can both open it)
Route::middleware('throttle:60,1')->prefix('explore')->name('explore.')->group(function () {
    Route::get('/', [ExploreController::class, 'index'])->name('index');
    Route::get('/{business:slug}', [ExploreController::class, 'show'])->name('show');
});

Route::get('/verify/{documentRequest:tracking_number}', [VerificationController::class, 'show'])
    ->middleware(['signed', 'throttle:30,1'])->name('documents.verify');

/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
| Access map (enforced here on the server; hiding menu items is only cosmetic):
|
|   CITIZEN
|     - Request Documents  (/documents)  -> verified residents only
|     - Market Prices      (/market)
|     - Community Polls    (/polls)      -> verified residents only
|     - Explore Market     (/explore)    -> public
|
|   BARANGAY STAFF
|     - Request Documents  (/documents)
|     - Payments           (/payments)
|     - Emergency          (/emergency)
|     - Document Queues    (/staff/documents and related review routes)
|
|   ADMIN
|     - Everything above, plus all remaining pages (command center, poll management,
|       treasury ledger, dispatch, My Businesses, resident/business review, staff market prices)
|
| 'resident.verified' only gates citizens; staff, treasury, MDRRMO and admin accounts pass through.
*/

// 'verified' here is Laravel's email-verification alias; it is separate from 'resident.verified'.
// Unverified citizens are redirected from the dashboard to the Guide, which is their home page.
Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'resident.verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    // ---- Shared account pages (any signed-in user, not menu items) --------------------------
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // News, guidelines and how-to-request: the landing area for unverified accounts
    Route::get('/guide', GuideController::class)->name('guide.index');

    // Services: every page the signed-in role can open, grouped (the mobile bottom bar's "Services" tab)
    Route::get('/services', fn () => Inertia::render('Services'))->name('services.index');

    // Private requirement files: owner or reviewing staff only (authorised in the controller)
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');

    // ---- CITIZEN (+ admin): Market Prices + Community Polls ---------------------------------
    Route::middleware('role:citizen,admin')->group(function () {
        Route::get('/market', [MarketWatchController::class, 'index'])->name('market.index');

        // Community Polls require a verified resident
        Route::middleware('resident.verified')->group(function () {
            Route::get('/polls', [PollController::class, 'index'])->name('polls.index');
            Route::post('/polls/{poll}/vote', [PollController::class, 'vote'])
                ->middleware('throttle:10,1')->name('polls.vote');
        });
    });

    // ---- CITIZEN + BARANGAY STAFF (+ admin): Request Documents ------------------------------
    Route::middleware(['role:citizen,barangay_staff,admin', 'resident.verified'])->group(function () {
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('/documents', [DocumentController::class, 'store'])
            ->middleware('throttle:10,1')->name('documents.store');
        Route::get('/documents/{documentRequest}/qr', [DocumentController::class, 'qr'])->name('documents.qr');

        // Upload / replace one requirement on the user's own request
        Route::post('/documents/{documentRequest}/requirements/{requirement}', [DocumentController::class, 'resubmit'])
            ->middleware('throttle:20,1')->name('documents.resubmit');

        // Link one of the user's approved documents (e.g. a Barangay Clearance) to a requirement
        Route::post('/documents/{documentRequest}/requirements/{requirement}/link', [DocumentController::class, 'link'])
            ->middleware('throttle:20,1')->name('documents.link');
    });

    // ---- ADMIN ONLY: My Businesses (not in the citizen or barangay staff menus) -------------
    Route::middleware(['role:admin', 'resident.verified'])->group(function () {
        Route::get('/my-businesses', [MyBusinessController::class, 'index'])->name('businesses.index');
        Route::get('/my-businesses/create', [MyBusinessController::class, 'create'])->name('businesses.create');
        Route::post('/my-businesses', [MyBusinessController::class, 'store'])->middleware('throttle:6,1')->name('businesses.store');
        Route::get('/my-businesses/{business}/edit', [MyBusinessController::class, 'edit'])->name('businesses.edit');
        Route::put('/my-businesses/{business}', [MyBusinessController::class, 'update'])->name('businesses.update');
        Route::delete('/my-businesses/{business}', [MyBusinessController::class, 'destroy'])->name('businesses.destroy');
    });

    // ---- BARANGAY STAFF (+ admin): Payments + Emergency -------------------------------------
    Route::middleware('role:barangay_staff,admin')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::post('/payments/{payment:reference_no}/resume', [PaymentController::class, 'resume'])->name('payments.resume');

        Route::get('/emergency', [EmergencyController::class, 'index'])->name('emergency.index');
        Route::post('/emergency', [EmergencyController::class, 'store'])
            ->middleware('throttle:3,1')->name('emergency.store');

        if (! app()->isProduction()) {
            Route::get('/payments/sandbox/{payment:reference_no}', [CheckoutController::class, 'show'])->name('payments.sandbox');
            Route::post('/payments/sandbox/{payment:reference_no}', [CheckoutController::class, 'complete'])->name('payments.sandbox.complete');
        }
    });
});

/*
|--------------------------------------------------------------------------
| Role-based areas
|--------------------------------------------------------------------------
*/

// BARANGAY STAFF: Document Queues
// city_staff = City Hall clerks (handle city-level requests such as business permits)
Route::middleware(['auth', 'role:barangay_staff,city_staff,admin'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/documents', [DocumentReviewController::class, 'index'])->name('documents.index');
    Route::patch('/documents/{documentRequest}', [DocumentReviewController::class, 'update'])->name('documents.update');

    Route::post('/documents/{documentRequest}/assess', [FeeAssessmentController::class, 'store'])->name('documents.assess');

    // Requirement verification (route names: staff.documents.review, staff.attachments.verify/reject)
    Route::get('/documents/{documentRequest}/review', [RequirementReviewController::class, 'show'])->name('documents.review');
    Route::post('/attachments/{attachment}/verify', [RequirementReviewController::class, 'verify'])->name('attachments.verify');
    Route::post('/attachments/{attachment}/reject', [RequirementReviewController::class, 'reject'])->name('attachments.reject');
});

// Not part of the citizen or barangay-staff menus: admin (and city staff, as before)
Route::middleware(['auth', 'role:city_staff,admin'])->prefix('staff')->name('staff.')->group(function () {
    // City Hall counter: payment collected and document handed over (approved -> released)
    Route::get('/', [CounterController::class, 'index'])->name('index');
    Route::post('/{documentRequest}/release', [CounterController::class, 'release'])->name('release');

    Route::get('/market-prices', [MarketPriceController::class, 'index'])->name('market.index');
    Route::post('/market-prices', [MarketPriceController::class, 'store'])->name('market.store');

    // Resident verification: this is what unlocks a citizen's account
    Route::get('/residents', [ResidentController::class, 'index'])->name('residents.index');
    Route::patch('/residents/{resident}', [ResidentController::class, 'update'])->name('residents.update');

    Route::get('/businesses', [BusinessReviewController::class, 'index'])->name('businesses.index');
    Route::patch('/businesses/{business}', [BusinessReviewController::class, 'update'])->name('businesses.update');
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

require __DIR__ . '/auth.php';
