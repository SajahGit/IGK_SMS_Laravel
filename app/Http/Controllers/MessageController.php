<?php

namespace App\Http\Controllers;

use App\Models\MessageConversation;
use App\Models\SchoolMessage;
use App\Models\User;
use App\Notifications\SchoolMessageReceived;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $userId = Auth::id();
        $tab = $request->query('tab', 'all');

        $query = MessageConversation::query()
            ->whereHas('participants', fn (Builder $q) => $q->where('users.id', $userId))
            ->with([
                'participants' => fn ($q) => $q->select('users.id', 'users.name', 'users.role', 'users.email', 'users.branch_id')->with('branch:id,name,code'),
                'messages' => fn ($q) => $q->with('sender:id,name,role')->latest()->limit(1),
            ])
            ->withCount([
                'messages as unread_messages_count' => fn (Builder $q) => $q
                    ->where('sender_id', '!=', $userId)
                    ->whereNull('read_at'),
            ]);

        if ($tab === 'unread') {
            $query->having('unread_messages_count', '>', 0);
        }

        $conversations = $query->orderByDesc('last_message_at')->paginate(15)->withQueryString();

        return view('Messages.Index', compact('conversations', 'tab'));
    }

    public function create(Request $request): View
    {
        $currentUserId = Auth::id();
        $selectedRecipientId = $request->query('recipient_id');

        $recipients = User::query()
            ->where('is_active', true)
            ->whereKeyNot($currentUserId)
            ->with([
                'branch:id,name,code',
                'teacher:id,user_id,department_id,branch_id',
                'teacher.department:id,name',
                'student:id,user_id,admission_no,class_id,branch_id',
                'student.class:id,name,section',
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'branch_id']);

        $staffRoles = ['super-admin', 'super admin', 'admin', 'institution admin', 'registrar', 'accountant'];
        $groupedRecipients = [
            'staff' => $recipients->filter(fn ($u) => in_array(strtolower($u->role), $staffRoles, true)),
            'teachers' => $recipients->filter(fn ($u) => strtolower($u->role) === 'teacher'),
            'students' => $recipients->filter(fn ($u) => strtolower($u->role) === 'student'),
            'guardians' => $recipients->filter(fn ($u) => in_array(strtolower($u->role), ['parent', 'guardian'], true)),
        ];

        return view('Messages.Create', compact('recipients', 'groupedRecipients', 'selectedRecipientId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('is_active', true),
                Rule::notIn([Auth::id()]),
            ],
            'subject' => ['nullable', 'string', 'max:120', 'regex:/[A-Za-z]/'],
            'body' => ['required', 'string', 'min:5', 'max:2000', 'regex:/[A-Za-z]/'],
        ]);

        $sender = Auth::user();
        $senderId = $sender->id;
        $recipient = User::query()->where('is_active', true)->findOrFail($data['recipient_id']);
        $pairKey = hash('sha256', min($senderId, $recipient->id).':'.max($senderId, $recipient->id));

        $conversation = DB::transaction(function () use ($data, $senderId, $recipient, $pairKey): MessageConversation {
            $conversation = MessageConversation::query()->firstOrCreate(
                ['pair_key' => $pairKey],
                [
                    'subject' => $data['subject'] ?? null,
                    'created_by' => $senderId,
                    'last_message_at' => now(),
                ]
            );

            if (! empty($data['subject'])) {
                $conversation->update(['subject' => $data['subject']]);
            }

            $conversation->participants()->syncWithoutDetaching([
                $senderId => ['joined_at' => now()],
                $recipient->id => ['joined_at' => now()],
            ]);

            $conversation->messages()->create([
                'sender_id' => $senderId,
                'body' => $data['body'],
                'delivered_at' => now(),
            ]);
            $conversation->update(['last_message_at' => now()]);

            return $conversation;
        });

        $recipient->notify(new SchoolMessageReceived($conversation));

        AuditLogger::log('message_sent', $conversation, null, [
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
            'recipient_id' => $recipient->id,
        ]);

        return redirect()->route('messages.show', $conversation)->with('success', 'Message sent successfully.');
    }

    public function show(MessageConversation $conversation): View
    {
        $conversation = $this->accessibleConversation($conversation);
        $conversation->messages()
            ->where('sender_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation->load([
            'participants' => fn ($query) => $query->select('users.id', 'users.name', 'users.role', 'users.email', 'users.branch_id')->with('branch:id,name,code'),
            'messages.sender:id,name,role',
        ]);

        return view('Messages.Show', compact('conversation'));
    }

    public function storeReply(Request $request, MessageConversation $conversation): RedirectResponse
    {
        $conversation = $this->accessibleConversation($conversation);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000', 'regex:/[A-Za-z]/'],
        ]);

        $sender = Auth::user();
        $recipient = $conversation->participants()
            ->where('users.id', '!=', $sender->id)
            ->where('users.is_active', true)
            ->firstOrFail();

        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $data['body'],
            'delivered_at' => now(),
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);
        $recipient->notify(new SchoolMessageReceived($conversation));

        AuditLogger::log('message_sent', $conversation, null, [
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
        ]);

        return redirect()->route('messages.show', $conversation)->with('success', 'Reply sent successfully.');
    }

    /**
     * Strict confidentiality enforcement: Only the sender and designated recipient can access the conversation.
     */
    private function accessibleConversation(MessageConversation $conversation): MessageConversation
    {
        $isParticipant = $conversation->participants()
            ->where('users.id', Auth::id())
            ->exists();

        if (! $isParticipant) {
            abort(403, 'Unauthorized. Private messages can only be viewed by the sender and designated recipient.');
        }

        return $conversation;
    }
}
