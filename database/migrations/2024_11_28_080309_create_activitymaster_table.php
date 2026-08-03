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
        Schema::create('activitymaster', function (Blueprint $table) {
            $table->id();
            
            // User Tracking & Relationship
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('user_name', 150)->nullable(); // Backup in case user is permanently deleted
            $table->string('user_email', 150)->nullable()->index(); // Quick identification in logs
            
            // Action & Request Details
            $table->string('method', 10)->index(); // GET, POST, PUT, DELETE, CLI, etc.
            $table->string('action_type', 50)->index(); // LOGIN, LOGOUT, CREATE, UPDATE, DELETE, FAILED_LOGIN
            $table->string('table_name', 100)->nullable()->index(); // Affected Database Table
            $table->unsignedBigInteger('record_id')->nullable()->index(); // ID of the row affected
            
            // Detailed Message & Context
            $table->text('log_text'); // Human-readable description of what happened
            $table->string('route_url', 255)->nullable(); // Exact URL/Endpoint hit
            
            // Network & Device Intelligence (Security Tracking)
            $table->string('ip_address', 45)->nullable()->index(); // Supports IPv4 and IPv6
            $table->text('user_agent')->nullable(); // Browser & OS details
            $table->string('device_type', 50)->nullable()->index(); // Mobile, Desktop, Tablet, Bot
            $table->string('location_country', 100)->nullable(); // GeoIP tracking optional field
            
            // Payload Data (Using native JSON for searching inside old/new data)
            $table->json('old_data')->nullable(); // Previous state of the model
            $table->json('updated_data')->nullable(); // New state of the model
            $table->json('additional_meta')->nullable(); // Extra custom debugging parameters
            
            // Severity / Status Level for Security Alerts
            $table->enum('severity', ['info', 'warning', 'danger', 'critical'])->default('info')->index(); 
            
            // Timestamps
            $table->timestamp('logged_at')->useCurrent()->index();
            $table->timestamps();

            // High-Performance Composite Indexes for Heavy Admin Dashboards & Filters
            $table->index(['action_type', 'logged_at'], 'idx_activity_action_time');
            $table->index(['user_id', 'logged_at'], 'idx_activity_user_time');
            $table->index(['table_name', 'record_id'], 'idx_activity_table_record');
            $table->index(['severity', 'logged_at'], 'idx_activity_severity_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activitymaster');
    }
};