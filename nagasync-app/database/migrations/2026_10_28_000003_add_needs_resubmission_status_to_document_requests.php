<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the 'needs_resubmission' status. MySQL/MariaDB only (the project runs on XAMPP MariaDB).
 *
 * If your status column is a plain string rather than an ENUM, delete this
 * migration; no schema change is needed. Check with: SHOW COLUMNS FROM document_requests LIKE 'status';
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            return;
        }

        DB::statement("ALTER TABLE document_requests MODIFY status
            ENUM('pending','processing','needs_resubmission','approved','rejected','revoked')
            NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            return;
        }

        DB::table('document_requests')->where('status', 'needs_resubmission')->update(['status' => 'pending']);

        DB::statement("ALTER TABLE document_requests MODIFY status
            ENUM('pending','processing','approved','rejected','revoked')
            NOT NULL DEFAULT 'pending'");
    }
};
