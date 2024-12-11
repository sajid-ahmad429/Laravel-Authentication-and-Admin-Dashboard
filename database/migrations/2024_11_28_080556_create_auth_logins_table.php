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
            $table->id(); // UNSIGNED BIGINT with auto-increment
            $table->unsignedBigInteger('user_id'); // UNSIGNED BIGINT for user ID
            $table->string('name', 255); // VARCHAR for name
            $table->string('role', 255); // VARCHAR for role
            $table->string('ip_address', 45); // VARCHAR for IP address
            $table->timestamp('date')->useCurrent(); // TIMESTAMP with default CURRENT_TIMESTAMP
            $table->boolean('successful'); // TINYINT(1) for success (true/false)
            $table->timestamps(); // Includes `created_at` and `updated_at`

            // Foreign key constraint (optional, adjust if `users` table exists)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
