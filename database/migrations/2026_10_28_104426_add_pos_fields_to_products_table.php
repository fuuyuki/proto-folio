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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->string('type', 16)->default('product')->index();
            $table->string('barcode', 64)->nullable()->unique();
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->boolean('track_stock')->default(true);
            $table->boolean('is_active')->default(true)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('unit_id');
            $table->dropConstrainedForeignId('tax_rate_id');
            $table->dropUnique(['barcode']);
            $table->dropColumn(['type', 'barcode', 'cost_price', 'track_stock', 'is_active']);
        });
    }
};
