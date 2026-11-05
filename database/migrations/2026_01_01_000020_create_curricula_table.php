<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curricula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedTinyInteger('term_number')->default(1);
            $table->boolean('is_elective')->default(false);
            $table->timestamps();
            $table->unique(['program_id', 'subject_id', 'term_number']);
            $table->index('program_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curricula');
    }
};
