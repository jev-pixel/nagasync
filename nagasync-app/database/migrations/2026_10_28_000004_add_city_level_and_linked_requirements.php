<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which office handles the request: the barangay or City Hall
        Schema::table('document_requests', function (Blueprint $table) {
            $table->string('level', 20)->default('barangay')->after('document_type')->index();
        });

        // A requirement is either an uploaded file or an already-issued NagaSync document
        Schema::table('document_requirements', function (Blueprint $table) {
            $table->string('kind', 20)->default('upload')->after('description');          // upload | issued_document
            $table->string('satisfied_by_type')->nullable()->after('kind');               // e.g. 'Business Clearance'
            $table->boolean('allow_upload_fallback')->default(true)->after('satisfied_by_type');
            $table->unsignedSmallInteger('max_age_days')->nullable()->after('allow_upload_fallback');
        });

        // An attachment is either a file or a link to an issued document
        Schema::table('request_attachments', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
            $table->string('original_name')->nullable()->change();
            $table->string('mime_type')->nullable()->change();
            $table->unsignedInteger('size')->nullable()->change();

            $table->foreignId('linked_document_request_id')
                ->nullable()
                ->after('document_requirement_id')
                ->constrained('document_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('request_attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('linked_document_request_id');
            // file_path/original_name/mime_type/size stay nullable on rollback; restoring NOT NULL
            // would fail if linked rows exist.
        });

        Schema::table('document_requirements', function (Blueprint $table) {
            $table->dropColumn(['kind', 'satisfied_by_type', 'allow_upload_fallback', 'max_age_days']);
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropIndex(['level']);
            $table->dropColumn('level');
        });
    }
};
