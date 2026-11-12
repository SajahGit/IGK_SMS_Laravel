<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->text('shared_secret');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('biometric_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_device_id')->constrained()->restrictOnDelete();
            $table->string('event_id', 100);
            $table->string('admission_no', 40)->index();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('scanned_at');
            $table->json('payload');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
            $table->unique(['biometric_device_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_events');
        Schema::dropIfExists('biometric_devices');
    }
};
