<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('conversations', function (Blueprint $table) {
            $table->uuid('profile_id')->nullable()->after('id');
        });

        Schema::table('pending_actions', function (Blueprint $table) {
            $table->uuid('profile_id')->nullable()->after('conversation_id');
        });

        Schema::table('audit_log', function (Blueprint $table) {
            $table->uuid('profile_id')->nullable()->after('id');
        });
    }

    public function down(): void {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('profile_id');
        });
        Schema::table('pending_actions', function (Blueprint $table) {
            $table->dropColumn('profile_id');
        });
        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropColumn('profile_id');
        });
    }
};
