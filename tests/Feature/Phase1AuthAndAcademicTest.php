<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1AuthAndAcademicTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@school.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@school.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => 'Account disabled, contact administrator.']);
        $this->assertGuest();
    }

    public function test_active_user_login_records_last_login_at(): void
    {
        $user = User::create([
            'name' => 'Active Admin',
            'email' => 'active@school.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->assertNull($user->last_login_at);

        $response = $this->post('/login', [
            'email' => 'active@school.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    public function test_user_with_must_change_password_is_redirected_to_password_change(): void
    {
        $user = User::create([
            'name' => 'Must Change Admin',
            'email' => 'mustchange@school.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => true,
        ]);

        // Login attempt redirects directly to password.change
        $response = $this->post('/login', [
            'email' => 'mustchange@school.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('password.change'));

        // Browsing any dashboard page redirects to password.change
        $dashResponse = $this->actingAs($user)->get(route('admin.dashboard'));
        $dashResponse->assertRedirect(route('password.change'));

        // Can access password.change page
        $pageResponse = $this->actingAs($user)->get(route('password.change'));
        $pageResponse->assertStatus(200);

        // Submitting new password clears must_change_password
        $updateResponse = $this->actingAs($user)->post(route('password.change.update'), [
            'current_password' => 'password',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $updateResponse->assertRedirect(route('admin.dashboard'));
        $user->refresh();
        $this->assertFalse($user->must_change_password);

        // Can now access dashboard freely
        $afterResponse = $this->actingAs($user)->get(route('admin.dashboard'));
        $afterResponse->assertStatus(200);
    }

    public function test_academic_year_is_current_invariant(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_acad@school.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $year1 = AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_current' => true,
        ]);

        $this->assertTrue($year1->is_current);

        // Create year2 as current via HTTP
        $this->actingAs($admin)->post(route('admin.academic-years.store'), [
            'name' => '2026/2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => 1,
        ]);

        $year1->refresh();
        $year2 = AcademicYear::where('name', '2026/2027')->first();

        $this->assertFalse($year1->is_current);
        $this->assertTrue($year2->is_current);

        // Now set year1 back to current via setCurrent route
        $this->actingAs($admin)->post(route('admin.academic-years.set-current', $year1));

        $year1->refresh();
        $year2->refresh();

        $this->assertTrue($year1->is_current);
        $this->assertFalse($year2->is_current);
    }

    public function test_term_is_current_invariant(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_term@school.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $term1 = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-30',
            'is_current' => true,
        ]);

        $this->assertTrue($term1->is_current);

        // Store Term 2 as current
        $this->actingAs($admin)->post(route('admin.terms.store'), [
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'sequence' => 2,
            'start_date' => '2026-05-01',
            'end_date' => '2026-08-31',
            'is_current' => 1,
        ]);

        $term1->refresh();
        $term2 = Term::where('name', 'Term 2')->first();

        $this->assertFalse($term1->is_current);
        $this->assertTrue($term2->is_current);
    }

    public function test_schema_upgrades(): void
    {
        $schoolClass = SchoolClass::create(['name' => 'Grade 11', 'section' => 'A']);
        $this->assertEquals(40, $schoolClass->capacity);

        $subject = Subject::create(['name' => 'Physics', 'code' => 'PHY']);
        $this->assertEquals(3, $subject->credit_hours);

        $year = AcademicYear::create([
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-30',
            'is_current' => true,
        ]);

        $teacherUser = User::create(['name' => 'Teacher', 'email' => 't@s.t', 'password' => 'password', 'role' => 'teacher']);

        $exam = Exam::create([
            'name' => 'Physics Finals',
            'type' => 'final',
            'class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'term_id' => $term->id,
            'exam_date' => '2026-11-20',
            'max_marks' => 100,
            'weight' => 100,
            'created_by' => $teacherUser->id,
            'is_published' => false,
        ]);

        $this->assertEquals('final', $exam->type);
        $this->assertEquals(100, $exam->weight);
        $this->assertFalse($exam->is_published);
        $this->assertEquals($term->id, $exam->term->id);

        $studentUser = User::create(['name' => 'Student', 'email' => 's@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $schoolClass->id,
            'admission_no' => 'ADM999',
        ]);

        // Absent result with null marks
        $absentResult = Result::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'marks' => null,
            'is_absent' => true,
        ]);

        $this->assertTrue($absentResult->is_absent);
        $this->assertNull($absentResult->marks);

        // Attendance locked_at timestamp
        $attendance = Attendance::create([
            'student_id' => $student->id,
            'class_id' => $schoolClass->id,
            'date' => '2026-10-27',
            'status' => 'present',
            'locked_at' => now(),
        ]);

        $this->assertNotNull($attendance->locked_at);
    }

    public function test_login_rate_limiting_throttles_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'throttletest@school.test',
                'password' => 'wrongpassword',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt should be throttled (429 status code)
        $throttledResponse = $this->post('/login', [
            'email' => 'throttletest@school.test',
            'password' => 'wrongpassword',
        ]);

        $throttledResponse->assertStatus(429);
    }
}
