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
    public function index(): View
    {
        $userId = Auth::id();
        $conversations = MessageConversation::query()
            ->whereHas('participants', fn (Builder $query) => $query->where('users.id', $userId))
            ->with(['participants:id,name,role', 'messages' => fn ($query) => $query->latest()->limit(1)])
            ->withCount([
                'messages as unread_messages_count' => fn (Builder $query) => $query
                    ->where('sender_id', '!=', $userId)
                    ->whereNull('read_at'),
            ])
            ->orderByDesc('last_message_at')
            ->paginate(15);

        return view('Messages.Index', compact('conversations'));
    }

    public function create(): View
    {
        $recipients = User::query()
            ->where('is_active', true)
            ->whereKeyNot(Auth::id())
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('Messages.Create', compact('recipients'));
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

        $senderId = Auth::id();
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

        return redirect()->route('messages.show', $conversation)->with('success', 'Message sent.');
    }

    public function show(MessageConversation $conversation): View
    {
        $conversation = $this->accessibleConversation($conversation);
        $conversation->messages()
            ->where('sender_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        $conversation->load(['participants:id,name,role', 'messages.sender:id,name,role']);

        return view('Messages.Show', compact('conversation'));
    }

    public function storeReply(Request $request, MessageConversation $conversation): RedirectResponse
    {
        $conversation = $this->accessibleConversation($conversation);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);
        $recipient = $conversation->participants()
            ->where('users.id', '!=', Auth::id())
            ->where('users.is_active', true)
            ->firstOrFail();

        $message = $conversation->messages()->create([
            'sender_id' => Auth::id(),
            'body' => $data['body'],
            'delivered_at' => now(),
        ]);
        $conversation->update(['last_message_at' => $message->created_at]);
        $recipient->notify(new SchoolMessageReceived($conversation));
        AuditLogger::log('message_sent', $conversation, null, [
            'conversation_id' => $conversation->id,
            'sender_id' => Auth::id(),
            'recipient_id' => $recipient->id,
        ]);

        return redirect()->route('messages.show', $conversation)->with('success', 'Reply sent.');
    }

    private function accessibleConversation(MessageConversation $conversation): MessageConversation
    {
        return MessageConversation::query()
            ->whereKey($conversation->id)
            ->whereHas('participants', fn (Builder $query) => $query->where('users.id', Auth::id()))
            ->firstOrFail();
    }
}
