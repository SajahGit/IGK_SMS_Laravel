<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('file_path', 500);
            $table->string('original_name', 255);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'type', 'title']);
            $table->index(['course_id', 'type', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_course_materials');
    }
};
