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
        Schema::table('users', function (Blueprint $table) {
            // Add indexes for frequently queried columns
            $table->index('email', 'idx_users_email');
            $table->index('contact_no', 'idx_users_contact');
            $table->index('status', 'idx_users_status');
            $table->index('trash', 'idx_users_trash');
            $table->index('activated', 'idx_users_activated');
            $table->index('roles', 'idx_users_roles');
            
            // Composite indexes for common query patterns
            $table->index(['status', 'trash'], 'idx_users_status_trash');
            $table->index(['email', 'status'], 'idx_users_email_status');
            $table->index(['contact_no', 'status'], 'idx_users_contact_status');
            
            // Add created_at and updated_at indexes for sorting and date-based queries
            $table->index('created_at', 'idx_users_created_at');
            $table->index('updated_at', 'idx_users_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_contact');
            $table->dropIndex('idx_users_status');
            $table->dropIndex('idx_users_trash');
            $table->dropIndex('idx_users_activated');
            $table->dropIndex('idx_users_roles');
            $table->dropIndex('idx_users_status_trash');
            $table->dropIndex('idx_users_email_status');
            $table->dropIndex('idx_users_contact_status');
            $table->dropIndex('idx_users_created_at');
            $table->dropIndex('idx_users_updated_at');
        });
    }
};
