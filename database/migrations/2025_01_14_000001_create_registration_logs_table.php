<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username');
            $table->string('ip_hash')->nullable();
            $table->timestamp('registered_at');
            $table->timestamps();
            
            $table->index('registered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_logs');
    }
};
