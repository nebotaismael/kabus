<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_terms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('term', 100);
            $table->uuid('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('source', 20)->default('products'); // 'products' or 'home'
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('term');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_terms');
    }
};
