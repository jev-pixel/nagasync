<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('market_prices') && DB::table('market_prices')->exists()) {
            throw new RuntimeException('market_prices contains rows. Migrate them manually.');
        }

        Schema::dropIfExists('market_prices');

        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->string('market')->default('Public Market');
            $table->string('commodity');
            $table->string('category')->default('other');
            $table->string('unit', 20)->default('kg');
            $table->unsignedInteger('price');            // centavos
            $table->unsignedInteger('srp')->nullable();  // centavos, official suggested price if any
            $table->date('recorded_on');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['market', 'commodity', 'unit', 'recorded_on']);
            $table->index(['commodity', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_prices');
    }
};