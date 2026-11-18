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
        Schema::table('invoices', function (Blueprint $table) {
            // Payment status and fulfillment progress are independent: an
            // invoice can be paid while the print is still queued.
            $table->string('order_status', 16)->default('sent')->index()->after('status');
            $table->string('fulfillment', 16)->nullable()->after('order_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['order_status']);
            $table->dropColumn(['order_status', 'fulfillment']);
        });
    }
};
