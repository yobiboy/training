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
        Schema::create('routing_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disbursement_voucher_id')->constrained('disbursement_vouchers');
            $table->foreignId('processing_stage_id')->constrained('processing_stages');
            $table->string('action', 30);
            $table->text('remarks')->nullable();
            $table->foreignId('acted_by')->constrained('users');
            $table->timestamp('acted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routing_histories');
    }
};
