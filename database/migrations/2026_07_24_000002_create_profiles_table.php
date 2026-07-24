<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('meta_account_id');
            $table->string('meta_account_name')->nullable();
            $table->string('meta_currency', 10)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'meta_account_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('profiles');
    }
};
