<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('conversation_members', 'last_read_at')) {
            Schema::table('conversation_members', function (Blueprint $table) {
                $table->timestamp('last_read_at')->nullable()->after('member_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('conversation_members', 'last_read_at')) {
            Schema::table('conversation_members', function (Blueprint $table) {
                $table->dropColumn('last_read_at');
            });
        }
    }
};
