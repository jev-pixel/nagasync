<?php

/*
 * ADD these routes to routes/web.php (inside your existing auth middleware group).
 * Imports needed at the top of the file:
 *
 *   use App\Http\Controllers\AttachmentController;
 *   use App\Http\Controllers\Citizen\DocumentController;
 *   use App\Http\Controllers\Staff\RequirementReviewController;
 */

// --- Citizen -------------------------------------------------------------
Route::post('documents/{documentRequest}/requirements/{requirement}', [DocumentController::class, 'resubmit'])
    ->name('documents.resubmit');

// --- Private file viewer (owner or reviewer; authorisation is in the controller) ---
Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])
    ->name('attachments.show');

// --- Staff / admin review ------------------------------------------------
// Put these inside the group that already restricts to staff/admin roles.
Route::get('staff/documents/{documentRequest}/review', [RequirementReviewController::class, 'show'])
    ->name('staff.documents.review');
Route::post('staff/attachments/{attachment}/verify', [RequirementReviewController::class, 'verify'])
    ->name('staff.attachments.verify');
Route::post('staff/attachments/{attachment}/reject', [RequirementReviewController::class, 'reject'])
    ->name('staff.attachments.reject');
