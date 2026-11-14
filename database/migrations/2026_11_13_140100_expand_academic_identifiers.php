<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->string('section', 10)->change();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('code', 15)->change();
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('code', 10)->change();
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->string('section', 5)->change();
        });
    }
};
