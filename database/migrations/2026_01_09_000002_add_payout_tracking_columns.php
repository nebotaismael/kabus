<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add payout tracking columns for NowPayments integration.
     * - Orders: vendor_payout_id, buyer_payout_id for tracking payouts
     * - Vendor payments: refund_payout_id for tracking refunds
     * - Advertisements: NowPayments columns for payment processing
     */
    public function up(): void
    {
        // Add payout tracking columns to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->string('vendor_payout_id')->nullable()->after('vendor_payment_at');
            $table->string('buyer_payout_id')->nullable()->after('buyer_refund_at');
        });

        // Add refund payout tracking to vendor_payment_subaddresses table
        Schema::table('vendor_payment_subaddresses', function (Blueprint $table) {
            $table->string('refund_payout_id')->nullable()->after('refund_address');
        });

        // Add NowPayments columns to advertisements table
        Schema::table('advertisements', function (Blueprint $table) {
            $table->string('np_payment_id')->nullable()->after('payment_identifier');
            $table->string('pay_address')->nullable()->after('np_payment_id');
            $table->decimal('pay_amount', 18, 12)->nullable()->after('pay_address');
            $table->string('pay_currency', 10)->default('xmr')->after('pay_amount');

            // Add index for faster lookups
            $table->index('np_payment_id');
        });

        // Remove legacy column from advertisements (separate statement for safety)
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropColumn('payment_address_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['vendor_payout_id', 'buyer_payout_id']);
        });

        // Restore vendor_payment_subaddresses table
        Schema::table('vendor_payment_subaddresses', function (Blueprint $table) {
            $table->dropColumn('refund_payout_id');
        });

        // Restore advertisements table
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropIndex(['np_payment_id']);
            $table->dropColumn(['np_payment_id', 'pay_address', 'pay_amount', 'pay_currency']);
            $table->integer('payment_address_index')->nullable()->after('payment_address');
        });
    }
};
