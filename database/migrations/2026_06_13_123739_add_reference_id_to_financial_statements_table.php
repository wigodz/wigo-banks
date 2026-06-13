<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('financial_statements', function (Blueprint $table) {
            $table->foreignId('reference_id')
                ->nullable()
                ->constrained('financial_statements')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_statements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reference_id');
        });
    }
};
