<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SOP §15.1 / US-F1: "fee structure exists for program+year" — the SOP bills on
 * program + academic year, not class + term. This migration adds that as the real
 * billing basis WITHOUT removing class_id/term_id, so nothing already built on the
 * class+term shape breaks:
 *
 *  - fee_structures.program_id / academic_year_id : the SOP-exact scope. A structure
 *    can now be created program-wide (class_id left null) or, as before, for one class.
 *  - class_id is made nullable so a program-wide structure needs no class.
 *  - Existing rows are back-filled: program_id from their class, academic_year_id
 *    from their term (or from the class's own academic_year_id when the structure
 *    has no term).
 *
 * Additive and safe on existing data, same pattern as 000029/000030. Uses raw SQL
 * for the nullability change so it does not require doctrine/dbal (not installed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            if (! Schema::hasColumn('fee_structures', 'program_id')) {
                $table->foreignId('program_id')->nullable()->after('class_id')->constrained('programs')->nullOnDelete();
            }
            if (! Schema::hasColumn('fee_structures', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('term_id')->constrained('academic_years')->nullOnDelete();
            }
        });

        // class_id was NOT NULL (every structure used to need one). A program-wide
        // structure (SOP-exact) has no single class, so loosen the constraint.
        // Raw SQL: avoids requiring doctrine/dbal just for a nullability change.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->unsignedBigInteger('class_id')->nullable()->change();
            });
        } else {
            DB::statement('ALTER TABLE fee_structures MODIFY class_id BIGINT UNSIGNED NULL');
        }

        // Back-fill program_id from the structure's class.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('
                UPDATE fee_structures
                SET program_id = (SELECT classes.program_id FROM classes WHERE classes.id = fee_structures.class_id)
                WHERE program_id IS NULL AND class_id IS NOT NULL
            ');
        } else {
            DB::statement('
                UPDATE fee_structures
                INNER JOIN classes ON classes.id = fee_structures.class_id
                SET fee_structures.program_id = classes.program_id
                WHERE fee_structures.program_id IS NULL AND fee_structures.class_id IS NOT NULL
            ');
        }

        // Back-fill academic_year_id from the structure's term when it has one.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('
                UPDATE fee_structures
                SET academic_year_id = (SELECT terms.academic_year_id FROM terms WHERE terms.id = fee_structures.term_id)
                WHERE academic_year_id IS NULL AND term_id IS NOT NULL
            ');
        } else {
            DB::statement('
                UPDATE fee_structures
                INNER JOIN terms ON terms.id = fee_structures.term_id
                SET fee_structures.academic_year_id = terms.academic_year_id
                WHERE fee_structures.academic_year_id IS NULL AND fee_structures.term_id IS NOT NULL
            ');
        }

        // No term on the structure: fall back to the class's own academic year.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('
                UPDATE fee_structures
                SET academic_year_id = (SELECT classes.academic_year_id FROM classes WHERE classes.id = fee_structures.class_id)
                WHERE academic_year_id IS NULL AND term_id IS NULL AND class_id IS NOT NULL
            ');
        } else {
            DB::statement('
                UPDATE fee_structures
                INNER JOIN classes ON classes.id = fee_structures.class_id
                SET fee_structures.academic_year_id = classes.academic_year_id
                WHERE fee_structures.academic_year_id IS NULL AND fee_structures.term_id IS NULL AND fee_structures.class_id IS NOT NULL
            ');
        }
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            if (Schema::hasColumn('fee_structures', 'program_id')) {
                $table->dropForeign(['program_id']);
                $table->dropColumn('program_id');
            }
            if (Schema::hasColumn('fee_structures', 'academic_year_id')) {
                $table->dropForeign(['academic_year_id']);
                $table->dropColumn('academic_year_id');
            }
        });

        DB::statement('UPDATE fee_structures SET class_id = (SELECT id FROM classes LIMIT 1) WHERE class_id IS NULL');
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->unsignedBigInteger('class_id')->nullable(false)->change();
            });
        } else {
            DB::statement('ALTER TABLE fee_structures MODIFY class_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
