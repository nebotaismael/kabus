<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add NowPayments.io integration columns to orders and vendor_payment_subaddresses tables.
     * Remove legacy Monero RPC columns that are no longer needed.
     */
    public function up(): void
    {
        // Update orders table
        Schema::table('orders', function (Blueprint $table) {
            // Add NowPayments columns
            $table->string('np_payment_id')->nullable()->after('payment_address');
            $table->string('pay_address')->nullable()->after('np_payment_id');
            $table->decimal('pay_amount', 18, 12)->nullable()->after('pay_address');
            $table->string('pay_currency', 10)->default('xmr')->after('pay_amount');

            // Add index for faster lookups
            $table->index('np_payment_id');
        });

        // Remove legacy column from orders (separate statement for safety)
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_address_index');
        });

        // Update vendor_payment_subaddresses table
        Schema::table('vendor_payment_subaddresses', function (Blueprint $table) {
            // Add NowPayments columns
            $table->string('np_payment_id')->nullable()->after('address');
            $table->string('pay_currency', 10)->default('xmr')->after('np_payment_id');

            // Add index for faster lookups
            $table->index('np_payment_id');
        });

        // Remove legacy column from vendor_payment_subaddresses (separate statement for safety)
        Schema::table('vendor_payment_subaddresses', function (Blueprint $table) {
            $table->dropColumn('address_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['np_payment_id']);
            $table->dropColumn(['np_payment_id', 'pay_address', 'pay_amount', 'pay_currency']);
            $table->integer('payment_address_index')->nullable()->after('payment_address');
        });

        // Restore vendor_payment_subaddresses table
        Schema::table('vendor_payment_subaddresses', function (Blueprint $table) {
            $table->dropIndex(['np_payment_id']);
            $table->dropColumn(['np_payment_id', 'pay_currency']);
            $table->unsignedInteger('address_index')->after('address');
        });
    }
};
