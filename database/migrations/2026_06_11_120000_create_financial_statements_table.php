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
        Schema::create('financial_statements', function (Blueprint $table) {
            $table->id();
            $table->string('hash')->nullable()->unique();
            $table->unsignedTinyInteger('type');
            $table->unsignedTinyInteger('operation_type');
            $table->boolean('reversed')->default(false);
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('receiver_id')->constrained('users');
            $table->integer('amount');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_statements');
    }
};
