<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_subject_id')->constrained('class_subject')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1 = Monday ... 5 = Friday
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_slots');
    }
};
