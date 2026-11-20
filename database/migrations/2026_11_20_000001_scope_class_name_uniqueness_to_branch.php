<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique('classes_name_section_unique');
            $table->unique(['name', 'section', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique('classes_name_section_branch_id_unique');
            $table->unique(['name', 'section']);
        });
    }
};
