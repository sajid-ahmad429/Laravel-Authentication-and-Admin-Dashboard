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
            $table->id(); // Auto-increment UNSIGNED BIGINT (Primary Key)
            $table->integer('method');
            $table->string('tableName', 255);
            $table->string('logText', 255);
            $table->string('address', 255);
            $table->integer('user_id');
            $table->string('user_name', 255);
            $table->timestamp('timestamp')->useCurrent()->useCurrentOnUpdate();
            $table->binary('old_data')->nullable(); // BLOB type
            $table->binary('updated_data')->nullable(); // BLOB type
            $table->binary('where_to')->nullable(); // BLOB type
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
