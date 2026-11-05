<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('section', 5);
            $table->timestamps();
            $table->unique(['name', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
