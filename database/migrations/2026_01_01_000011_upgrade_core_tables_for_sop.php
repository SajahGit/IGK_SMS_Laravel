<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('must_change_password');
        });

        // 2. Create academic_years
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        // 3. Create terms
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        // 4. Update classes
        Schema::table('classes', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->default(40)->after('section');
        });

        // 5. Update subjects
        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedTinyInteger('credit_hours')->default(3)->after('code');
        });

        // 6. Update attendance
        Schema::table('attendance', function (Blueprint $table) {
            $table->timestamp('locked_at')->nullable()->after('marked_by');
        });

        // 7. Update exams
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('term_id')->nullable()->after('subject_id')->constrained('terms')->nullOnDelete();
            $table->string('type')->default('final')->after('name');
            $table->unsignedTinyInteger('weight')->default(100)->after('max_marks');
            $table->boolean('is_published')->default(false)->after('created_by');
            $table->timestamp('published_at')->nullable()->after('is_published');
        });

        // 8. Update results
        Schema::table('results', function (Blueprint $table) {
            $table->boolean('is_absent')->default(false)->after('marks');
            $table->decimal('marks', 6, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->decimal('marks', 6, 2)->nullable(false)->change();
            $table->dropColumn('is_absent');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['term_id']);
            $table->dropColumn(['term_id', 'type', 'weight', 'is_published', 'published_at']);
        });

        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn('locked_at');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('credit_hours');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('capacity');
        });

        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_years');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'must_change_password', 'last_login_at']);
        });
    }
};
