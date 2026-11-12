<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_id')->constrained()->restrictOnDelete();
            $table->string('room_number', 40);
            $table->unsignedSmallInteger('bed_capacity');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['hostel_id', 'room_number']);
        });

        Schema::create('hostel_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('hostel_room_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('bed_number');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->index(['student_id', 'academic_year_id', 'status']);
            $table->index(['hostel_room_id', 'bed_number', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_assignments');
        Schema::dropIfExists('hostel_rooms');
        Schema::dropIfExists('hostels');
    }
};
