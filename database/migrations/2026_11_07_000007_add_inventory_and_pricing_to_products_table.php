<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock_quantity')->nullable()->after('price')->index();
            $table->unsignedBigInteger('cost_price')->nullable()->after('stock_quantity');
            $table->unsignedSmallInteger('profit_percent')->nullable()->after('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['stock_quantity']);
            $table->dropColumn(['stock_quantity', 'cost_price', 'profit_percent']);
        });
    }
};
