<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_notification_details_and_list_and_cannot_view_others(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true, 'must_change_password' => false]);
        $other = User::factory()->create(['role' => 'student', 'is_active' => true, 'must_change_password' => false]);

        $notification = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotice',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => [
                'title' => 'Exam Schedule Published',
                'body' => 'Final term exams begin next Monday at 9:00 AM.',
                'url' => '/student/dashboard',
            ],
        ]);

        // Clicking from dropdown (POST read) marks read and redirects to detail view
        $this->actingAs($user)
            ->post(route('notifications.read', $notification))
            ->assertRedirect(route('notifications.show', $notification));

        $this->assertNotNull($notification->fresh()->read_at);

        // Detail page renders full title, body, and related link
        $this->actingAs($user)
            ->get(route('notifications.show', $notification))
            ->assertOk()
            ->assertSee('Exam Schedule Published')
            ->assertSee('Final term exams begin next Monday at 9:00 AM.')
            ->assertSee('Open Related Page');

        // Notifications list page renders cleanly
        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Exam Schedule Published');

        // Another user cannot view this notification
        $this->actingAs($other)
            ->get(route('notifications.show', $notification))
            ->assertNotFound();

        // Stale localhost URL with root path resolves to role-appropriate related page on current host
        $invoiceNotification = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\InvoiceIssuedNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => [
                'title' => 'New invoice issued',
                'body' => 'Invoice INV-1001 is due.',
                'url' => 'http://localhost/',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('notifications.show', $invoiceNotification))
            ->assertOk()
            ->assertSee(route('student.invoices'));

        // Stale localhost URL with path strips localhost and uses current host
        $msgNotification = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\SchoolMessageReceived',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => [
                'title' => 'Private message',
                'body' => 'Hello!',
                'url' => 'http://localhost/messages',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('notifications.show', $msgNotification))
            ->assertOk()
            ->assertSee(url('/messages'));
    }
}
