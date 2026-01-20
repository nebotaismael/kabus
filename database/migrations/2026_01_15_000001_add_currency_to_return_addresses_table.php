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
        Schema::table('return_addresses', function (Blueprint $table) {
            // Rename monero_address to address for multi-currency support
            $table->renameColumn('monero_address', 'address');
        });

        Schema::table('return_addresses', function (Blueprint $table) {
            // Add currency column with default 'xmr' for existing addresses
            $table->string('currency', 10)->default('xmr')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_addresses', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('return_addresses', function (Blueprint $table) {
            $table->renameColumn('address', 'monero_address');
        });
    }
};
