<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'tool']);
            $table->longText('content');
            $table->string('tool_call_id')->nullable();
            $table->string('tool_name')->nullable();
            $table->timestamps();
        });

        Schema::create('pending_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('tool_name');
            $table->json('tool_input');
            $table->text('preview');
            $table->json('conversation_state');
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pending_action_id')->constrained();
            $table->string('tool_name');
            $table->json('tool_input');
            $table->text('preview')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('meta_response')->nullable();
            $table->boolean('approved');
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('label')->unique();
            $table->text('encrypted_data');
            $table->text('iv');
            $table->text('tag');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('pending_actions');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('credentials');
    }
};
