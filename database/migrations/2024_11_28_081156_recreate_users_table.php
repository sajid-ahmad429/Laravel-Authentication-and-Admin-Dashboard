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
        // Drop the users table if it exists
        Schema::dropIfExists('users');
        // Create the users table
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // UNSIGNED BIGINT with auto-increment
            $table->string('name'); // Name of the user
            $table->string('email')->unique(); // Email with a unique constraint
            $table->string('contact_no')->nullable(); // Contact number (nullable)
            $table->string('company_name')->nullable(); // Company name (nullable)
            $table->string('country')->nullable(); // Country (nullable)
            $table->string('roles')->nullable(); // Roles (nullable)
            $table->string('plan')->nullable(); // Plan (nullable)
            $table->timestamp('email_verified_at')->nullable(); // Email verification timestamp
            $table->string('password'); // Password
            $table->string('reset_token')->nullable(); // Reset token (nullable)
            $table->dateTime('reset_expire')->nullable(); // Reset expiry datetime (nullable)
            $table->boolean('activated')->default(0); // Account activation status (default false)
            $table->string('activate_token')->nullable(); // Activation token (nullable)
            $table->dateTime('activate_expire')->nullable(); // Activation expiry datetime (nullable)
            $table->rememberToken(); // Remember token
            $table->integer('status')->default(1); // Status (default 1)
            $table->integer('trash')->default(0); // Trash status (default 0)
            $table->timestamps(); // Created at and updated at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('users'); // Drop the users table
    }
};
