<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The 1x1 photo is an ordinary uploaded requirement, so the PDF reads it from the attachment.
 * photo_path was added by 2026_11_19_035526 and was never used.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('document_requests', 'photo_path')) {
            Schema::table('document_requests', fn (Blueprint $t) => $t->dropColumn('photo_path'));
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('document_requests', 'photo_path')) {
            Schema::table('document_requests', fn (Blueprint $t) => $t->string('photo_path')->nullable());
        }
    }
};
