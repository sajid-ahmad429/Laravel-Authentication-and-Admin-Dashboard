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
        // 1. Users Table (Complete Production Schema with Indexes)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default('');
            $table->string('email')->unique();
            $table->string('contact_no')->nullable();
            $table->string('company_name')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('roles')->nullable(); // or role
            $table->string('plan')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Security & Auth Tokens
            $table->string('reset_token')->nullable();
            $table->dateTime('reset_expire')->nullable();
            $table->boolean('activated')->default(0);
            $table->string('activate_token')->nullable();
            $table->dateTime('activate_expire')->nullable();
            
            // Status & Management Flags
            $table->integer('status')->default(1);
            $table->integer('trash')->default(0);
            
            $table->rememberToken();
            $table->timestamps();

            // All Performance Indexes Combined Here
            $table->index('email', 'idx_users_email');
            $table->index('contact_no', 'idx_users_contact');
            $table->index('status', 'idx_users_status');
            $table->index('trash', 'idx_users_trash');
            $table->index('activated', 'idx_users_activated');
            $table->index('roles', 'idx_users_roles');
            
            // Composite Indexes
            $table->index(['status', 'trash'], 'idx_users_status_trash');
            $table->index(['email', 'status'], 'idx_users_email_status');
            $table->index(['contact_no', 'status'], 'idx_users_contact_status');
            
            // Date Indexes
            $table->index('created_at', 'idx_users_created_at');
            $table->index('updated_at', 'idx_users_updated_at');
        });

        // 2. Password Reset Tokens Table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // 3. Sessions Table with Cascade Delete
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};