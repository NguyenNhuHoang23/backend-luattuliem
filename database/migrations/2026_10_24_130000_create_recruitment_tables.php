<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('intro');
            $table->timestamps();
        });

        Schema::create('recruitment_roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('apply_label')->nullable();
            $table->json('audience')->nullable();
            $table->json('audience_note')->nullable();
            $table->json('work')->nullable();
            $table->json('work_note')->nullable();
            $table->json('income')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });

        Schema::create('recruitment_posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->json('paragraphs')->nullable();
            $table->string('role')->nullable();
            $table->unsignedInteger('publication_order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_posts');
        Schema::dropIfExists('recruitment_roles');
        Schema::dropIfExists('recruitment_pages');
    }
};
