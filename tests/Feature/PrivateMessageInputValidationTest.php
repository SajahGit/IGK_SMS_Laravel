<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivateMessageInputValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_message_subject_and_body_require_letters_and_respect_limits(): void
    {
        $sender = User::create([
            'name' => 'Message Sender',
            'email' => 'message-sender@school.test',
            'password' => 'password',
            'role' => 'teacher',
            'is_active' => true,
        ]);
        $recipient = User::create([
            'name' => 'Message Recipient',
            'email' => 'message-recipient@school.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->actingAs($sender);

        $this->get(route('messages.create'))
            ->assertOk()
            ->assertSee('id="recipient_id"', false)
            ->assertSee('data-contains-letters', false)
            ->assertDontSee('size="8"', false);

        $this->post(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'subject' => '12345',
            'body' => '1234',
        ])->assertSessionHasErrors(['subject', 'body']);

        $this->post(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'subject' => str_repeat('S', 121),
            'body' => 'A'.str_repeat('b', 2000),
        ])->assertSessionHasErrors(['subject', 'body']);
    }
}
