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
        Schema::create('auth_logins', function (Blueprint $table) {
            $table->id();
            
            // User Relationship (Nullable because failed attempts might not match an existing user ID, or user could be deleted)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
                  
            // Snapshot of user details at the time of login (helpful if user is deleted later)
            $table->string('name', 150)->nullable();
            $table->string('email', 150)->index()->nullable(); // Useful for tracking brute-force attempts on specific emails
            $table->string('role', 50)->nullable()->index();
            
            // Network & Geolocation Security
            $table->string('ip_address', 45)->nullable()->index(); // IPv4 & IPv6 support
            $table->text('user_agent')->nullable(); // Browser & Platform details
            $table->string('device_type', 50)->nullable(); // Mobile, Desktop, Tablet
            
            // Login Status & Diagnostics
            $table->boolean('successful')->default(false)->index(); // true: Success, false: Failed attempt
            $table->string('failure_reason', 150)->nullable(); // e.g., 'Invalid Password', 'Account Deactivated', 'Blocked'
            
            // Timestamps
            $table->timestamp('logged_in_at')->useCurrent()->index();
            $table->timestamps();

            // High-Performance Composite Indexes for Security & Login History Dashboards
            $table->index(['user_id', 'logged_in_at'], 'idx_auth_user_time');
            $table->index(['successful', 'logged_in_at'], 'idx_auth_success_time');
            $table->index(['ip_address', 'logged_in_at'], 'idx_auth_ip_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_logins');
    }
};