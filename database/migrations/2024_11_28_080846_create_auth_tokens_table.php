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
        Schema::create('auth_tokens', function (Blueprint $table) {
            $table->id();
            
            // User Relationship with Cascade Delete (agar user delete ho toh uske saare active tokens delete ho jayein)
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
                  
            // Selector & Hashed Validator (Secure Token Pattern e.g., Remember Me / Remember Token)
            $table->string('selector', 100)->unique()->index(); // Unique selector for quick DB lookups
            $table->string('hashedvalidator', 255); // Secure hashed version of the validator string
            
            // Token Type / Scope (Optional but great for multi-purpose token systems like 'remember_me', 'api_access')
            $table->string('token_type', 50)->default('remember_me')->index();
            
            // Expiration tracking (Standard timestamp instead of auto-updating on every touch)
            $table->timestamp('expires_at')->index();
            
            $table->timestamps();

            // Composite Indexes for Fast Token Validation & Cleanup Queries
            $table->index(['user_id', 'token_type'], 'idx_auth_tokens_user_type');
            $table->index(['selector', 'expires_at'], 'idx_auth_tokens_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_tokens');
    }
};