<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('midtrans_token')->nullable()->unique();
            $table->string('midtrans_transaction_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['midtrans_token']);
            $table->dropUnique(['midtrans_transaction_id']);
            $table->dropColumn(['midtrans_token', 'midtrans_transaction_id']);
        });
    }
};
