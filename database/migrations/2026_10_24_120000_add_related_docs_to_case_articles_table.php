<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_articles', function (Blueprint $table) {
            $table->json('related_docs')->nullable()->after('case_body');
        });
    }

    public function down(): void
    {
        Schema::table('case_articles', function (Blueprint $table) {
            $table->dropColumn('related_docs');
        });
    }
};
