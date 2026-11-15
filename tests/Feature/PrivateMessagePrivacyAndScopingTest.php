<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivateMessagePrivacyAndScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipient_dropdown_shows_students_teachers_and_admin_staff(): void
    {
        $branch = Branch::create(['name' => 'Colombo Campus', 'code' => 'COL']);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@school.test',
            'password' => 'password',
            'role' => 'admin',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $accountant = User::create([
            'name' => 'Staff Accountant',
            'email' => 'accountant@school.test',
            'password' => 'password',
            'role' => 'accountant',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $teacherUser = User::create([
            'name' => 'Teacher One',
            'email' => 'teacher@school.test',
            'password' => 'password',
            'role' => 'teacher',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $dept = Department::create(['name' => 'Sciences', 'code' => 'SCI', 'branch_id' => $branch->id]);
        Teacher::create([
            'user_id' => $teacherUser->id,
            'department_id' => $dept->id,
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $studentUser = User::create([
            'name' => 'Student Aisha',
            'email' => 'student@school.test',
            'password' => 'password',
            'role' => 'student',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A', 'capacity' => 30, 'branch_id' => $branch->id]);
        Student::create([
            'user_id' => $studentUser->id,
            'admission_no' => 'COL/2026/0001',
            'class_id' => $class->id,
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('messages.create'));
        $response->assertOk();
        $response->assertSee('Staff Accountant');
        $response->assertSee('Teacher One');
        $response->assertSee('Student Aisha');
        $response->assertSee('data-category="teachers"', false);
        $response->assertSee('data-category="students"', false);
        $response->assertSee('data-category="staff"', false);
        $response->assertSee('id="recipientSearchInput"', false);
        $response->assertSee('id="liveSearchResultsPanel"', false);
        $response->assertSee('id="selectedRecipientBanner"', false);
    }

    public function test_messages_are_strictly_private_and_accessible_only_to_sender_and_recipient(): void
    {
        $admin = User::create([
            'name' => 'Principal Perera',
            'email' => 'principal@school.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $teacher = User::create([
            'name' => 'Mr. Silva',
            'email' => 'silva@school.test',
            'password' => 'password',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $unrelatedStudent = User::create([
            'name' => 'Kamal Student',
            'email' => 'kamal@school.test',
            'password' => 'password',
            'role' => 'student',
            'is_active' => true,
        ]);

        // Admin sends message to Teacher
        $this->actingAs($admin);
        $response = $this->post(route('messages.store'), [
            'recipient_id' => $teacher->id,
            'subject' => 'Confidential Salary Review',
            'body' => 'Please attend the confidential review meeting on Monday.',
        ]);

        $response->assertRedirect();

        // Admin sees the conversation in inbox with "You sent to Mr. Silva"
        $inboxResponse = $this->get(route('messages.index'));
        $inboxResponse->assertOk();
        $inboxResponse->assertSee('Mr. Silva');
        $inboxResponse->assertSee('You sent to Mr. Silva');
        $inboxResponse->assertSee('Confidential Salary Review');

        // Teacher logs in: sees the conversation with "Received from Principal Perera"
        $this->actingAs($teacher);
        $teacherInbox = $this->get(route('messages.index'));
        $teacherInbox->assertOk();
        $teacherInbox->assertSee('Principal Perera');
        $teacherInbox->assertSee('Received from Principal Perera');
        $teacherInbox->assertSee('Confidential Salary Review');

        // Teacher opens the conversation
        $conversation = \App\Models\MessageConversation::first();
        $this->assertNotNull($conversation);

        $teacherView = $this->get(route('messages.show', $conversation));
        $teacherView->assertOk();
        $teacherView->assertSee('Principal Perera');
        $teacherView->assertSee('Please attend the confidential review meeting on Monday.');

        // Unrelated Student logs in: must NOT see the conversation in inbox
        $this->actingAs($unrelatedStudent);
        $studentInbox = $this->get(route('messages.index'));
        $studentInbox->assertOk();
        $studentInbox->assertDontSee('Confidential Salary Review');
        $studentInbox->assertDontSee('Principal Perera');

        // Unrelated Student attempts to directly access the URL: MUST BE FORBIDDEN (403)
        $studentDirectAccess = $this->get(route('messages.show', $conversation));
        $studentDirectAccess->assertStatus(403);
    }

    public function test_notification_sent_only_to_recipient_with_sender_details(): void
    {
        $sender = User::create([
            'name' => 'Mrs. Fernando',
            'email' => 'fernando@school.test',
            'password' => 'password',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $recipient = User::create([
            'name' => 'Nimal Parent',
            'email' => 'nimal@school.test',
            'password' => 'password',
            'role' => 'parent',
            'is_active' => true,
        ]);

        $otherUser = User::create([
            'name' => 'Third Party',
            'email' => 'third@school.test',
            'password' => 'password',
            'role' => 'student',
            'is_active' => true,
        ]);

        $this->actingAs($sender);
        $this->post(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'subject' => 'Student Progress Report',
            'body' => 'Your child showed great improvement this term in Mathematics.',
        ]);

        $this->assertEquals(1, $recipient->notifications()->count());
        $notification = $recipient->notifications()->first();
        $this->assertStringContainsString('Private message from Mrs. Fernando', $notification->data['title']);

        $this->assertEquals(0, $otherUser->notifications()->count());
    }
}
