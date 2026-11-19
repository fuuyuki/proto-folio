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
            // The original STL/3MF lives on the private disk. The original
            // name is kept for the admin download, not the public payment page.
            $table->string('model_path', 500)->nullable()->after('fulfillment');
            $table->string('model_name', 255)->nullable()->after('model_path');
            $table->string('model_mime', 100)->nullable()->after('model_name');
            $table->unsignedBigInteger('model_size')->nullable()->after('model_mime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['model_path', 'model_name', 'model_mime', 'model_size']);
        });
    }
};
