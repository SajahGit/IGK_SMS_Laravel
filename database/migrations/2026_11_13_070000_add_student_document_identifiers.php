<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('document_type', 20)->nullable()->after('national_id');
            $table->char('issuing_country', 2)->nullable()->after('document_type');
            $table->string('document_number', 30)->nullable()->after('issuing_country');
        });

        DB::table('students')
            ->whereNull('document_number')
            ->whereNotNull('national_id')
            ->update(['document_number' => DB::raw('national_id')]);

        Schema::table('students', function (Blueprint $table) {
            $table->unique('document_number', 'students_document_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_document_number_unique');
            $table->dropColumn(['document_type', 'issuing_country', 'document_number']);
        });
    }
};
