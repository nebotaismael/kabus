<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Extends the phrase column from 16 to 32 characters to support
     * longer anti-phishing phrases as per Requirement 6.1.
     */
    public function up(): void
    {
        Schema::table('secret_phrases', function (Blueprint $table) {
            $table->string('phrase', 32)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('secret_phrases', function (Blueprint $table) {
            $table->string('phrase', 16)->change();
        });
    }
};
