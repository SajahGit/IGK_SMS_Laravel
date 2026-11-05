<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (! Schema::hasColumn('teachers', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('teachers', 'status')) {
                $table->string('status', 20)->default('active')->after('phone');
            }
        });

        Schema::table('classes', function (Blueprint $table) {
            if (! Schema::hasColumn('classes', 'program_id')) {
                $table->foreignId('program_id')->nullable()->after('section')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('classes', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('program_id')->constrained()->nullOnDelete();
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'program_id')) {
                $table->foreignId('program_id')->nullable()->after('class_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('students', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('program_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('students', 'gender')) {
                $table->string('gender', 20)->nullable()->after('dob');
            }
            if (! Schema::hasColumn('students', 'status')) {
                $table->string('status', 20)->default('active')->after('phone');
            }
            if (! Schema::hasColumn('students', 'admission_date')) {
                $table->date('admission_date')->nullable()->after('admission_no');
            }
        });

        Schema::table('class_subject', function (Blueprint $table) {
            if (! Schema::hasIndex('class_subject', 'class_subject_class_id_index')) {
                $table->index('class_id', 'class_subject_class_id_index');
            }
            if (Schema::hasIndex('class_subject', 'class_subject_class_id_subject_id_unique')) {
                $table->dropUnique('class_subject_class_id_subject_id_unique');
            }
            if (! Schema::hasColumn('class_subject', 'term_id')) {
                $table->foreignId('term_id')->nullable()->after('class_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasIndex('class_subject', 'class_subject_class_subject_term_unique')) {
                $table->unique(['class_id', 'subject_id', 'term_id'], 'class_subject_class_subject_term_unique');
            }
        });

        Schema::table('announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('announcements', 'program_id')) {
                $table->foreignId('program_id')->nullable()->after('target_role')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('announcements', 'class_id')) {
                $table->foreignId('class_id')->nullable()->after('program_id')->constrained('classes')->nullOnDelete();
            }
            if (! Schema::hasColumn('announcements', 'notifications_sent_at')) {
                $table->timestamp('notifications_sent_at')->nullable()->after('expires_at');
            }
        });

        if (! Schema::hasTable('guardian_student')) {
            Schema::create('guardian_student', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
                $table->unique(['guardian_id', 'student_id']);
                $table->index(['student_id', 'is_primary']);
            });
        }

        DB::table('students')
            ->whereNotNull('guardian_id')
            ->orderBy('id')
            ->get(['id', 'guardian_id', 'created_at', 'updated_at'])
            ->each(function ($student) {
                DB::table('guardian_student')->insertOrIgnore([
                    'guardian_id' => $student->guardian_id,
                    'student_id' => $student->id,
                    'is_primary' => true,
                    'created_at' => $student->created_at,
                    'updated_at' => $student->updated_at,
                ]);
            });

        if (! Schema::hasTable('attendance_sessions')) {
            Schema::create('attendance_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('class_subject_id')->nullable()->constrained('class_subject')->nullOnDelete();
                $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
                $table->date('date');
                $table->string('session_label', 80)->default('General');
                $table->foreignId('taken_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('locked_at')->nullable();
                $table->timestamps();
                $table->index(['class_id', 'date']);
                $table->index(['class_subject_id', 'date']);
            });
        }

        Schema::table('attendance', function (Blueprint $table) {
            if (! Schema::hasIndex('attendance', 'attendance_student_id_index')) {
                $table->index('student_id', 'attendance_student_id_index');
            }
            if (Schema::hasIndex('attendance', 'attendance_student_id_date_unique')) {
                $table->dropUnique('attendance_student_id_date_unique');
            }
            if (! Schema::hasColumn('attendance', 'attendance_session_id')) {
                $table->foreignId('attendance_session_id')->nullable()->after('class_id')
                    ->constrained('attendance_sessions')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('attendance', 'remarks')) {
                $table->text('remarks')->nullable()->after('status');
            }
            if (! Schema::hasColumn('attendance', 'marked_at')) {
                $table->timestamp('marked_at')->nullable()->after('marked_by');
            }
            if (! Schema::hasIndex('attendance', 'attendance_student_session_unique')) {
                $table->unique(['student_id', 'attendance_session_id'], 'attendance_student_session_unique');
            }
        });

        DB::table('attendance')
            ->whereNull('attendance_session_id')
            ->select(['class_id', 'date'])
            ->distinct()
            ->orderBy('class_id')
            ->orderBy('date')
            ->get()
            ->each(function ($row) {
                $sessionId = DB::table('attendance_sessions')->insertGetId([
                    'class_id' => $row->class_id,
                    'date' => $row->date,
                    'session_label' => 'Legacy daily attendance',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('attendance')
                    ->where('class_id', $row->class_id)
                    ->where('date', $row->date)
                    ->update(['attendance_session_id' => $sessionId]);
            });

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_inbox_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::table('attendance', function (Blueprint $table) {
            $table->dropUnique('attendance_student_session_unique');
            $table->dropConstrainedForeignId('attendance_session_id');
            $table->dropColumn(['remarks', 'marked_at']);
            $table->unique(['student_id', 'date']);
            $table->dropIndex('attendance_student_id_index');
        });
        Schema::dropIfExists('attendance_sessions');

        Schema::dropIfExists('guardian_student');

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('notifications_sent_at');
            $table->dropConstrainedForeignId('class_id');
            $table->dropConstrainedForeignId('program_id');
        });

        Schema::table('class_subject', function (Blueprint $table) {
            $table->dropUnique('class_subject_class_subject_term_unique');
            $table->dropConstrainedForeignId('term_id');
            $table->unique(['class_id', 'subject_id']);
            $table->dropIndex('class_subject_class_id_index');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_year_id');
            $table->dropConstrainedForeignId('program_id');
            $table->dropColumn(['gender', 'status', 'admission_date']);
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_year_id');
            $table->dropConstrainedForeignId('program_id');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn('status');
        });
    }
};
