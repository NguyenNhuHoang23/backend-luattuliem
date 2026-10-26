<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('about_articles', function (Blueprint $table) {
            $table->boolean('status')->default(false)->after('publication_order');
        });
    }

    public function down(): void
    {
        Schema::table('about_articles', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
