<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number', 30)->unique();
            $table->string('vehicle_type', 40);
            $table->unsignedSmallInteger('capacity');
            $table->string('driver_name', 120);
            $table->string('driver_phone', 30)->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('inspection_expiry')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 120);
            $table->foreignId('vehicle_id')->nullable()->constrained('transport_vehicles')->nullOnDelete();
            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'name']);
        });

        Schema::create('transport_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_route_id')->constrained('transport_routes')->restrictOnDelete();
            $table->string('name', 120);
            $table->unsignedSmallInteger('sequence');
            $table->string('address', 500)->nullable();
            $table->time('pickup_time')->nullable();
            $table->time('dropoff_time')->nullable();
            $table->timestamps();
            $table->unique(['transport_route_id', 'sequence']);
            $table->index(['transport_route_id', 'name']);
        });

        Schema::create('student_transport_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('transport_route_id')->constrained('transport_routes')->restrictOnDelete();
            $table->foreignId('transport_stop_id')->constrained('transport_stops')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->index(['student_id', 'academic_year_id', 'status'], 'sta_student_year_status_idx');
            $table->index(['transport_route_id', 'academic_year_id', 'status'], 'sta_route_year_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transport_assignments');
        Schema::dropIfExists('transport_stops');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('transport_vehicles');
    }
};
