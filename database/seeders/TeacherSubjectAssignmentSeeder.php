<?php

namespace Database\Seeders;

use App\Models\ClassSubject;
use App\Models\Department;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;

class TeacherSubjectAssignmentSeeder extends Seeder
{
    private const SUBJECT_DEPARTMENTS = [
        'ENP' => 'EARLY',
        'NUM' => 'EARLY',
        'PLS' => 'EARLY',
        'SEN' => 'EARLY',
        'WLD' => 'EARLY',
        'ENG' => 'LANG',
        'LIT' => 'LANG',
        'FRE' => 'LANG',
        'SPA' => 'LANG',
        'MAN' => 'LANG',
        'ARA' => 'LANG',
        'TAM' => 'LANG',
        'SIN' => 'LANG',
        'HIN' => 'LANG',
        'JPN' => 'LANG',
        'SWA' => 'LANG',
        'MAL' => 'LANG',
        'ZUL' => 'LANG',
        'AFR' => 'LANG',
        'APENG' => 'LANG',
        'MTH' => 'MATH',
        'MATH' => 'MATH',
        'FMA' => 'MATH',
        'APCALC' => 'MATH',
        'BIO' => 'SCI',
        'CHE' => 'SCI',
        'PHY' => 'SCI',
        'SCI' => 'SCI',
        'ENV' => 'SCI',
        'APBIO' => 'SCI',
        'APCHEM' => 'SCI',
        'APPHYS' => 'SCI',
        'HIS' => 'HUM',
        'GEO' => 'HUM',
        'ECO' => 'HUM',
        'BUS' => 'HUM',
        'ACC' => 'HUM',
        'PSY' => 'HUM',
        'GPR' => 'HUM',
        'TOK' => 'HUM',
        'EE' => 'HUM',
        'CAS' => 'HUM',
        'APUSH' => 'HUM',
        'APECON' => 'HUM',
        'ART' => 'ARTS',
        'MUS' => 'ARTS',
        'PE' => 'WELL',
        'ICT' => 'TECH',
        'CS' => 'TECH',
        'APCS' => 'TECH',
    ];

    private const PREFERRED_TEACHERS = [
        'MTH' => 'teacher@school.test',
        'MATH' => 'teacher@school.test',
        'FMA' => 'teacher@school.test',
        'APCALC' => 'teacher@school.test',
        'ENG' => 'teacher2@school.test',
        'LIT' => 'teacher2@school.test',
        'APENG' => 'teacher2@school.test',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo teacher subject assignments may only be seeded outside production.');
        }

        DB::transaction(function (): void {
            $departments = Department::query()
                ->whereIn('code', array_values(self::SUBJECT_DEPARTMENTS))
                ->pluck('id', 'code');
            $wellbeingDepartmentId = $departments->get('WELL');
            if (! $wellbeingDepartmentId) {
                throw new RuntimeException('Run AcademicStructureSeeder before assigning demo teacher subjects.');
            }
            $this->ensureWellbeingTeacher($wellbeingDepartmentId);

            $teachersByEmail = Teacher::query()
                ->with('user')
                ->get()
                ->keyBy(fn (Teacher $teacher) => $teacher->user->email);

            foreach ([
                'teacher@school.test' => 'MATH',
                'teacher2@school.test' => 'LANG',
            ] as $email => $departmentCode) {
                $teacher = $teachersByEmail->get($email);
                $departmentId = $departments->get($departmentCode);
                if (! $teacher || ! $departmentId) {
                    throw new RuntimeException("Demo teacher {$email} and department {$departmentCode} must exist before assigning subjects.");
                }

                if ($teacher->department_id !== $departmentId) {
                    $teacher->update(['department_id' => $departmentId]);
                }
            }

            $teachersByDepartment = Teacher::query()
                ->with('user')
                ->get()
                ->groupBy('department_id');
            $preferredEmails = array_unique(array_values(self::PREFERRED_TEACHERS));
            $departmentAssignmentCounts = [];

            ClassSubject::query()
                ->with(['subject', 'schoolClass'])
                ->orderBy('id')
                ->get()
                ->each(function (ClassSubject $offering) use (
                    $departments,
                    &$departmentAssignmentCounts,
                    $preferredEmails,
                    $teachersByEmail,
                    $teachersByDepartment
                ): void {
                    $subjectCode = $offering->subject?->code;
                    $departmentCode = self::SUBJECT_DEPARTMENTS[$subjectCode] ?? null;
                    if (! $departmentCode) {
                        return;
                    }

                    $departmentId = $departments->get($departmentCode);
                    if (! $departmentId) {
                        throw new RuntimeException("Academic department {$departmentCode} is missing for {$subjectCode}.");
                    }

                    $teacher = null;
                    $preferredEmail = self::PREFERRED_TEACHERS[$subjectCode] ?? null;
                    if ($preferredEmail && $this->isPreferredTeacherAssignment($subjectCode, $offering->schoolClass?->name ?? '')) {
                        $teacher = $teachersByEmail->get($preferredEmail);
                    }

                    if (! $teacher) {
                        $candidates = $teachersByDepartment->get($departmentId, collect())
                            ->reject(fn (Teacher $candidate) => in_array($candidate->user->email, $preferredEmails, true))
                            ->values();
                        if ($candidates->isEmpty()) {
                            $candidates = $teachersByDepartment->get($departmentId, collect())->values();
                        }
                        if ($candidates->isEmpty()) {
                            throw new RuntimeException("No demo teacher is assigned to department {$departmentCode} for {$subjectCode}.");
                        }

                        $assignmentIndex = $departmentAssignmentCounts[$departmentCode] ?? 0;
                        $teacher = $candidates[$assignmentIndex % $candidates->count()];
                        $departmentAssignmentCounts[$departmentCode] = $assignmentIndex + 1;
                    }

                    if ($offering->teacher_id !== $teacher->id) {
                        $offering->update(['teacher_id' => $teacher->id]);
                    }
                });
        });
    }

    private function ensureWellbeingTeacher(int $departmentId): void
    {
        if (Teacher::query()->where('department_id', $departmentId)->exists()) {
            return;
        }

        if (! Role::query()->where('name', 'Teacher')->where('guard_name', 'web')->exists()) {
            $this->call(RolePermissionSeeder::class);
        }

        $user = User::firstOrCreate(
            ['email' => 'demo.staff.wellbeing@school.test'],
            [
                'name' => 'Rafael Mensah',
                'password' => 'password',
                'role' => 'teacher',
                'branch_id' => User::query()->whereNotNull('branch_id')->orderBy('id')->value('branch_id'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        if (! $user->hasRole('Teacher')) {
            $user->syncRoles(['Teacher']);
        }

        $teacher = Teacher::firstOrCreate(
            ['user_id' => $user->id],
            ['department_id' => $departmentId, 'status' => 'active']
        );
        if ($teacher->department_id !== $departmentId) {
            $teacher->update(['department_id' => $departmentId]);
        }
    }

    private function isPreferredTeacherAssignment(string $subjectCode, string $className): bool
    {
        if (in_array($subjectCode, ['MTH', 'MATH', 'FMA', 'APCALC'], true)) {
            if (preg_match('/\AGrade (\d+)\z/', $className, $matches) !== 1) {
                return false;
            }

            $grade = (int) $matches[1];

            return $grade >= 9 && ($subjectCode !== 'FMA' && $subjectCode !== 'APCALC' || $grade >= 11);
        }

        if (in_array($subjectCode, ['ENG', 'LIT'], true)) {
            if (preg_match('/\AGrade (\d+)\z/', $className, $matches) === 1) {
                $grade = (int) $matches[1];

                return $grade >= 6 && $grade <= 10;
            }

            return preg_match('/\AYear ([7-9])\z/', $className) === 1;
        }

        return $subjectCode === 'APENG'
            && preg_match('/\AGrade (11|12)\z/', $className) === 1;
    }
}
