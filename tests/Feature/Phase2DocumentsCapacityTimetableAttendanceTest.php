<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\Department;
use App\Models\Guardian;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\AttendanceSessionService;
use App\Support\AdmissionNumberGenerator;
use App\Support\TimetableClashDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase2DocumentsCapacityTimetableAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequential_admission_number_generation(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $program = Program::create(['department_id' => $department->id, 'name' => 'Computer Science', 'code' => 'CS']);
        $academicYear = AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
        ]);
        $no1 = AdmissionNumberGenerator::next($program->id, $academicYear->id);
        $this->assertEquals('2026-CS-0001', $no1);

        $schoolClass = SchoolClass::create([
            'name' => 'Grade 10',
            'section' => 'A',
            'program_id' => $program->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $u1 = User::create(['name' => 'S1', 'email' => 's1@s.t', 'password' => 'password', 'role' => 'student']);
        Student::create(['user_id' => $u1->id, 'class_id' => $schoolClass->id, 'admission_no' => $no1]);

        $no2 = AdmissionNumberGenerator::next($program->id, $academicYear->id);
        $this->assertEquals('2026-CS-0002', $no2);

        $nextYear = AcademicYear::create([
            'name' => '2027-2028',
            'start_date' => '2027-01-01',
            'end_date' => '2028-12-31',
        ]);
        $this->assertEquals('2027-CS-0001', AdmissionNumberGenerator::next($program->id, $nextYear->id));
    }

    public function test_international_program_code_is_normalized_for_admission_numbers(): void
    {
        $department = Department::create(['name' => 'International Programmes', 'code' => 'INTL']);
        $program = Program::create([
            'department_id' => $department->id,
            'name' => 'Cambridge International IGCSE',
            'code' => 'CA-IGCSE',
        ]);
        $academicYear = AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
        ]);

        $this->assertSame(
            '2026-CAIGCS-',
            AdmissionNumberGenerator::prefix($program->id, $academicYear->id)
        );
    }

    public function test_student_admission_requires_at_least_one_guardian(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'guardian-check-admin@s.t', 'password' => 'password', 'role' => 'admin']);
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $program = Program::create(['department_id' => $department->id, 'name' => 'Computer Science', 'code' => 'CS']);
        $academicYear = AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
        ]);
        $class = SchoolClass::create([
            'name' => 'Grade 10',
            'section' => 'A',
            'program_id' => $program->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Student without guardian',
            'email' => 'no-guardian@s.t',
            'password' => 'Password123!',
            'class_id' => $class->id,
            'document_type' => 'passport',
            'issuing_country' => 'LK',
            'document_number' => 'NOGUARD1',
        ])->assertSessionHasErrors('guardian_ids');

        $this->assertDatabaseMissing('users', ['email' => 'no-guardian@s.t']);
    }

    public function test_attendance_sessions_are_unique_by_class_subject_date_and_start_time(): void
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MTH']);
        $user = User::create(['name' => 'Teacher', 'email' => 'attendance-session@s.t', 'password' => 'password', 'role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $user->id, 'phone' => '123']);
        $classSubject = ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
        $date = today()->toDateString();

        $session = AttendanceSessionService::create($class->id, $date, '08:00', $classSubject->id, $user->id);
        $this->assertSame('08:00', substr($session->fresh()->start_time, 0, 5));

        $secondSession = AttendanceSessionService::create($class->id, $date, '09:00', $classSubject->id, $user->id);
        $this->assertNotSame($session->id, $secondSession->id);

        try {
            AttendanceSessionService::create($class->id, $date, '08:00', $classSubject->id, $user->id);
            $this->fail('A duplicate attendance session should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('start_time', $exception->errors());
        }
    }

    public function test_staff_attendance_accepts_and_normalizes_browser_time_formats(): void
    {
        $admin = User::create([
            'name' => 'Attendance Admin',
            'email' => 'staff-attendance-admin@s.t',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $teacherUser = User::create([
            'name' => 'Attendance Teacher',
            'email' => 'staff-attendance-teacher@s.t',
            'password' => 'password',
            'role' => 'teacher',
        ]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'status' => 'active']);

        foreach ([
            ['08:00', '16:00'],
            ['08:00:00', '16:00:00'],
            ['08:00 AM', '04:00 PM'],
        ] as [$checkIn, $checkOut]) {
            $this->actingAs($admin)
                ->post(route('admin.staff-attendance.store'), [
                    'teacher_id' => $teacher->id,
                    'attendance_date' => today()->toDateString(),
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                ])
                ->assertRedirect();

            $record = StaffAttendance::query()->where('teacher_id', $teacher->id)->firstOrFail();
            $this->assertSame('08:00', substr((string) $record->check_in, 0, 5));
            $this->assertSame('16:00', substr((string) $record->check_out, 0, 5));
        }

        $this->actingAs($admin)
            ->get(route('admin.staff-attendance.index'))
            ->assertOk()
            ->assertSee('staff-attendance-table')
            ->assertSee('form="staff-attendance-'.$teacher->id.'"', false);
    }

    public function test_section_capacity_limit_enforced_on_enrollment(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@s.t', 'password' => 'password', 'role' => 'admin']);

        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $program = Program::create(['department_id' => $department->id, 'name' => 'Computer Science', 'code' => 'CS']);
        $academicYear = AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
        ]);
        $class = SchoolClass::create([
            'name' => 'Special Class',
            'section' => 'A',
            'capacity' => 1,
            'program_id' => $program->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $guardianUser = User::create(['name' => 'Guardian', 'email' => 'capacity-guardian@s.t', 'password' => 'password', 'role' => 'parent']);
        $guardian = Guardian::create(['user_id' => $guardianUser->id, 'phone' => '123']);

        // Enroll 1st student successfully
        $res1 = $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Student One',
            'email' => 'student1@s.t',
            'password' => 'Password123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
            'document_type' => 'passport',
            'issuing_country' => 'LK',
            'document_number' => 'CAPACITY1',
        ]);
        $res1->assertRedirect(route('admin.students.index'));
        $this->assertEquals(1, Student::where('class_id', $class->id)->count());

        // Attempt enrolling 2nd student into full class throws validation error
        $res2 = $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Student Two',
            'email' => 'student2@s.t',
            'password' => 'Password123!',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
            'document_type' => 'passport',
            'issuing_country' => 'LK',
            'document_number' => 'CAPACITY2',
        ]);
        $res2->assertSessionHasErrors('class_id');
        $this->assertEquals(1, Student::where('class_id', $class->id)->count());
    }

    public function test_student_document_private_storage_and_download(): void
    {
        Storage::fake('local');

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@s.t', 'password' => 'password', 'role' => 'admin']);
        $schoolClass = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $studentUser = User::create(['name' => 'Student', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $schoolClass->id, 'admission_no' => 'ADM001']);

        $file = UploadedFile::fake()->create('birth_certificate.pdf', 500, 'application/pdf');

        // Admin uploads document
        $response = $this->actingAs($admin)->post(route('admin.students.documents.upload', $student), [
            'type' => 'birth_cert',
            'document' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('student_documents', [
            'student_id' => $student->id,
            'type' => 'birth_cert',
            'original_name' => 'birth_certificate.pdf',
        ]);

        $doc = StudentDocument::where('student_id', $student->id)->first();
        Storage::disk('local')->assertExists($doc->path);

        // Student can download own document
        $dlResponse = $this->actingAs($studentUser)->get(route('student.documents.download', $doc));
        $dlResponse->assertStatus(200);

        // Another student cannot download
        $otherStudentUser = User::create(['name' => 'Other', 'email' => 'other@s.t', 'password' => 'password', 'role' => 'student']);
        Student::create(['user_id' => $otherStudentUser->id, 'class_id' => $schoolClass->id, 'admission_no' => 'ADM002']);

        $unauthResponse = $this->actingAs($otherStudentUser)->get(route('student.documents.download', $doc));
        $unauthResponse->assertStatus(403);
    }

    public function test_timetable_clash_detection(): void
    {
        $classA = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $classB = SchoolClass::create(['name' => 'Grade 10', 'section' => 'B']);

        $maths = Subject::create(['name' => 'Maths', 'code' => 'MTH']);
        $science = Subject::create(['name' => 'Science', 'code' => 'SCI']);

        $tUser1 = User::create(['name' => 'Teacher 1', 'email' => 't1@s.t', 'password' => 'password', 'role' => 'teacher']);
        $t1 = Teacher::create(['user_id' => $tUser1->id, 'phone' => '111']);

        $tUser2 = User::create(['name' => 'Teacher 2', 'email' => 't2@s.t', 'password' => 'password', 'role' => 'teacher']);
        $t2 = Teacher::create(['user_id' => $tUser2->id, 'phone' => '222']);

        $csA_Math = ClassSubject::create(['class_id' => $classA->id, 'subject_id' => $maths->id, 'teacher_id' => $t1->id]);
        $csB_Sci = ClassSubject::create(['class_id' => $classB->id, 'subject_id' => $science->id, 'teacher_id' => $t2->id]);
        $csB_Math = ClassSubject::create(['class_id' => $classB->id, 'subject_id' => $maths->id, 'teacher_id' => $t1->id]);

        // Slot 1: Class A, Maths with Teacher 1 on Monday (1) 08:00 - 09:00 in Room 101
        TimetableSlot::create([
            'class_subject_id' => $csA_Math->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'Room 101',
        ]);

        // 1. Room Clash: Different class & teacher, but same room & overlapping time
        $this->expectException(ValidationException::class);
        TimetableClashDetector::checkClash($csB_Sci->id, 1, '08:30', '09:30', 'Room 101');
    }

    public function test_timetable_teacher_clash_detected(): void
    {
        $classA = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $classB = SchoolClass::create(['name' => 'Grade 10', 'section' => 'B']);
        $maths = Subject::create(['name' => 'Maths', 'code' => 'MTH']);

        $tUser1 = User::create(['name' => 'Teacher 1', 'email' => 't1@s.t', 'password' => 'password', 'role' => 'teacher']);
        $t1 = Teacher::create(['user_id' => $tUser1->id, 'phone' => '111']);

        $csA = ClassSubject::create(['class_id' => $classA->id, 'subject_id' => $maths->id, 'teacher_id' => $t1->id]);
        $csB = ClassSubject::create(['class_id' => $classB->id, 'subject_id' => $maths->id, 'teacher_id' => $t1->id]);

        TimetableSlot::create([
            'class_subject_id' => $csA->id,
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'room' => 'Room 101',
        ]);

        // Teacher 1 cannot teach Class B in Room 102 at overlapping time
        $this->expectException(ValidationException::class);
        TimetableClashDetector::checkClash($csB->id, 2, '10:30', '11:30', 'Room 102');
    }

    public function test_attendance_48_hour_edit_window_and_registrar_override(): void
    {
        $tUser = User::create(['name' => 'Teacher', 'email' => 'teacher@s.t', 'password' => 'password', 'role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $tUser->id, 'phone' => '111']);
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $maths = Subject::create(['name' => 'Maths', 'code' => 'MTH']);
        $classSubject = ClassSubject::create(['class_id' => $class->id, 'subject_id' => $maths->id, 'teacher_id' => $teacher->id]);

        $studentUser = User::create(['name' => 'Student', 'email' => 'student@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'admission_no' => 'ADM001']);
        $classSubject->students()->attach($student->id, ['status' => 'enrolled']);
        $date = today()->toDateString();

        $session = AttendanceSession::create([
            'class_id' => $class->id,
            'class_subject_id' => $classSubject->id,
            'date' => $date,
            'start_time' => '08:00',
            'session_label' => 'Maths',
            'taken_by' => $tUser->id,
        ]);
        AttendanceSession::whereKey($session->id)->update(['created_at' => now()->subHours(49)]);
        Attendance::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'attendance_session_id' => $session->id,
            'date' => $date,
            'status' => 'present',
            'marked_by' => $tUser->id,
        ]);

        $payload = [
            'class_id' => $class->id,
            'date' => $date,
            'start_time' => '08:00',
            'class_subject_id' => $classSubject->id,
            'attendance_session_id' => $session->id,
            'status' => [$student->id => 'absent'],
        ];
        $this->actingAs($tUser)->post(route('teacher.attendance.store'), $payload)->assertStatus(403);

        Role::firstOrCreate(['name' => 'Registrar', 'guard_name' => 'web']);
        $registrar = User::create(['name' => 'Registrar', 'email' => 'registrar@s.t', 'password' => 'password', 'role' => 'registrar']);
        $registrar->assignRole('Registrar');
        $registrar->givePermissionTo(Permission::firstOrCreate(['name' => 'attendance.update', 'guard_name' => 'web']));
        $this->actingAs($registrar)
            ->post(route('admin.attendance.store'), $payload)
            ->assertRedirect(route('admin.attendance.create', ['session_id' => $session->id]));
        $this->assertDatabaseHas('attendance', [
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'absent',
        ]);
    }

    public function test_admin_timetable_index_and_store_slot(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'adm_tt@s.t', 'password' => 'password', 'role' => 'admin']);
        $class = SchoolClass::create(['name' => 'Grade 11', 'section' => 'A']);
        $sub = Subject::create(['name' => 'History', 'code' => 'HIS']);
        $tUser = User::create(['name' => 'Teacher H', 'email' => 'th@s.t', 'password' => 'password', 'role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $tUser->id, 'phone' => '999']);
        $cs = ClassSubject::create(['class_id' => $class->id, 'subject_id' => $sub->id, 'teacher_id' => $teacher->id]);

        // 1. Admin views timetable
        $res = $this->actingAs($admin)->get(route('admin.timetable.index', ['class_id' => $class->id]));
        $res->assertStatus(200);
        $res->assertSee('Class Timetable');
        $res->assertSee('Add Timetable Slot');
        $res->assertSee('createSlotModal');

        // 2. Admin stores new timetable slot
        $postRes = $this->actingAs($admin)->post(route('admin.timetable.store'), [
            'class_subject_id' => $cs->id,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'room' => 'Hall 4',
        ]);
        $postRes->assertRedirect(route('admin.timetable.index', ['class_id' => $class->id]));
        $this->assertDatabaseHas('timetable_slots', [
            'class_subject_id' => $cs->id,
            'day_of_week' => 1,
            'room' => 'Hall 4',
        ]);
    }
}
