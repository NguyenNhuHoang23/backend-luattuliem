<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('case_number');
            $table->string('code')->nullable();
            $table->string('category');
            $table->string('tag')->nullable();
            $table->text('description');
            $table->longText('overview')->nullable();
            $table->string('source_court')->nullable();
            $table->date('published_on')->nullable();
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('publication_order')->default(1);
            $table->string('field')->nullable();
            $table->string('status')->nullable();
            $table->json('keywords')->nullable();
            $table->json('legal_provisions')->nullable();
            $table->json('case_body')->nullable();
            $table->json('attributes')->nullable();
            $table->json('modal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_articles');
    }
};
