<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\AnnouncementDeliveryService;
use App\Support\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Phase5AnnouncementsAuditReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_announcements_audience_filtering(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@s.t', 'password' => 'password', 'role' => 'admin']);

        // Announcement for teachers only
        Announcement::create([
            'title' => 'Teacher Meeting',
            'body' => 'Staff briefing at 2 PM.',
            'target_role' => 'teacher',
            'published_at' => now()->subDay(),
            'created_by' => $admin->id,
        ]);

        // Announcement for all
        Announcement::create([
            'title' => 'School Holiday',
            'body' => 'School closed on Friday.',
            'target_role' => 'all',
            'published_at' => now()->subDay(),
            'created_by' => $admin->id,
        ]);

        // Announcement for students
        Announcement::create([
            'title' => 'Sports Meet',
            'body' => 'Practice begins next week.',
            'target_role' => 'student',
            'published_at' => now()->subDay(),
            'created_by' => $admin->id,
        ]);

        $teacherAnnouncements = Announcement::activeForRole('teacher')->pluck('title');
        $this->assertTrue($teacherAnnouncements->contains('Teacher Meeting'));
        $this->assertTrue($teacherAnnouncements->contains('School Holiday'));
        $this->assertFalse($teacherAnnouncements->contains('Sports Meet'));

        $studentAnnouncements = Announcement::activeForRole('student')->pluck('title');
        $this->assertFalse($studentAnnouncements->contains('Teacher Meeting'));
        $this->assertTrue($studentAnnouncements->contains('School Holiday'));
        $this->assertTrue($studentAnnouncements->contains('Sports Meet'));
    }

    public function test_targeted_announcement_delivers_in_app_and_queued_email_only_to_matching_role_and_section(): void
    {
        Queue::fake();
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@s.t', 'password' => 'password', 'role' => 'admin']);
        $targetClass = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $otherClass = SchoolClass::create(['name' => 'Grade 10', 'section' => 'B']);
        $targetUser = User::create(['name' => 'Target student', 'email' => 'target@s.t', 'password' => 'password', 'role' => 'student']);
        $targetStudent = Student::create(['user_id' => $targetUser->id, 'class_id' => $targetClass->id, 'admission_no' => 'A-1']);
        $otherUser = User::create(['name' => 'Other student', 'email' => 'other@s.t', 'password' => 'password', 'role' => 'student']);
        Student::create(['user_id' => $otherUser->id, 'class_id' => $otherClass->id, 'admission_no' => 'B-1']);
        $guardianUser = User::create(['name' => 'Target guardian', 'email' => 'guardian@s.t', 'password' => 'password', 'role' => 'parent']);
        $guardian = Guardian::create(['user_id' => $guardianUser->id, 'phone' => '555']);
        $targetStudent->guardians()->attach($guardian->id, ['is_primary' => true]);
        $announcement = Announcement::create([
            'title' => 'Section A reminder',
            'body' => 'Bring your workbook.',
            'target_role' => 'student',
            'class_id' => $targetClass->id,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        $this->assertTrue(app(AnnouncementDeliveryService::class)->deliver($announcement));
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $targetUser->id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $otherUser->id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $guardianUser->id,
        ]);
        Queue::assertPushed(SendQueuedNotifications::class);
        $this->assertFalse(Announcement::activeForUser($otherUser)->whereKey($announcement->id)->exists());
    }

    public function test_audit_logger_records_mutations(): void
    {
        $admin = User::create(['name' => 'Auditor Admin', 'email' => 'auditor@s.t', 'password' => 'password', 'role' => 'admin']);
        $this->actingAs($admin);

        $class = SchoolClass::create(['name' => 'Grade 12', 'section' => 'A']);

        AuditLogger::log('created_class', $class, null, ['name' => 'Grade 12', 'section' => 'A']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created_class',
            'subject_type' => SchoolClass::class,
            'subject_id' => $class->id,
        ]);

        $log = AuditLog::where('action', 'created_class')->first();
        $this->assertNotNull($log->new_values);
        $this->assertEquals('Grade 12', $log->new_values['name']);
    }

    public function test_reports_csv_streaming(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@s.t', 'password' => 'password', 'role' => 'admin']);
        $this->actingAs($admin);

        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A']);
        $subject = Subject::create(['name' => 'Maths', 'code' => 'MTH']);

        $u = User::create(['name' => 'S1', 'email' => 's1@s.t', 'password' => 'password', 'role' => 'student']);
        $student = Student::create(['user_id' => $u->id, 'class_id' => $class->id, 'admission_no' => 'ADM001']);

        $invoice = Invoice::create([
            'student_id' => $student->id,
            'invoice_no' => 'INV-2026-0001',
            'issue_date' => '2026-05-01',
            'due_date' => '2026-05-31',
            'total' => '10000.00',
            'amount_paid' => '5000.00',
            'balance' => '5000.00',
            'status' => 'partially_paid',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_no' => 'RCP-2026-0001',
            'amount' => '5000.00',
            'method' => 'cash',
            'paid_at' => '2026-05-05',
            'received_by' => $admin->id,
        ]);

        // 1. Class Attendance Summary CSV
        $resp1 = $this->get(route('reports.class-attendance.csv'));
        $resp1->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resp1->headers->get('Content-Type'));

        // 2. Subject Performance CSV
        $resp2 = $this->get(route('reports.subject-performance.csv'));
        $resp2->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resp2->headers->get('Content-Type'));

        // 3. Fee Collection CSV
        $resp3 = $this->get(route('reports.fee-collection.csv'));
        $resp3->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resp3->headers->get('Content-Type'));

        // 4. Defaulters CSV
        $resp4 = $this->get(route('reports.defaulters.csv'));
        $resp4->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resp4->headers->get('Content-Type'));
    }
}
