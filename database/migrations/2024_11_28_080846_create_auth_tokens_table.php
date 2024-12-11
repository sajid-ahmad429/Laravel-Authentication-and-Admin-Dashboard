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
            $table->id(); // UNSIGNED BIGINT with auto-increment
            $table->unsignedBigInteger('user_id'); // UNSIGNED BIGINT for user ID
            $table->string('selector', 255); // VARCHAR for selector
            $table->string('hashedvalidator', 255); // VARCHAR for hashed validator
            $table->timestamp('expires')->useCurrent()->useCurrentOnUpdate(); // TIMESTAMP with default CURRENT_TIMESTAMP and auto-update
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
        Schema::dropIfExists('auth_tokens');
    }
};
