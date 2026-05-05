<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('github_url')->nullable();
            $table->string('preview_image_path')->nullable();
            $table->json('technologies')->default('[]');
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'project_id']);
            $table->index('project_id');
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'project_id']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
        Schema::dropIfExists('project_user');
        Schema::dropIfExists('projects');
    }
};
