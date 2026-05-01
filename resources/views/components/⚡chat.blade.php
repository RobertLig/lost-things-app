<?php

use Livewire\Component;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;
use Livewire\Attributes\On;

new class extends Component {
    public $conversationId;
    public $message = '';

    protected $listeners = ['conversationSelected' => 'loadConversation'];

    public function mount($conversationId = null)
    {
        $this->conversationId = $conversationId;

        $this->markAsRead();
    }

    public function loadConversation($id)
    {
        $this->conversationId = $id;

        $this->markAsRead();
    }

    public function sendMessage()
    {
        if (!$this->message) {
            return;
        }

        $otherUserId = $this->getOtherUserId();

        if (auth()->user()->isBlockingOrBlocked($otherUserId)) {
            return; // blocked → do nothing
        }

        $message = Message::create([
            'conversation_id' => $this->conversationId,
            'sender_id' => auth()->id(),
            'body' => $this->message,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        // update conversation timestamp (important for sorting)
        Conversation::where('id', $this->conversationId)->update(['updated_at' => now()]);

        $this->reset('message');
    }

    public function getMessagesProperty()
    {
        if (!$this->conversationId) {
            return collect();
        }

        return Message::query()
            ->where('conversation_id', $this->conversationId)
            ->where(function ($q) {
                $userId = auth()->id();

                $q->where(function ($q2) use ($userId) {
                    $q2->where('sender_id', $userId)->whereNull('deleted_by_sender_at');
                })->orWhere(function ($q2) use ($userId) {
                    $q2->where('sender_id', '!=', $userId)->whereNull('deleted_by_receiver_at');
                });
            })
            ->with('sender')
            ->latest()
            ->take(50)
            ->get()
            ->reverse();
    }

    public function markAsRead()
    {
        if (!$this->conversationId) {
            return;
        }

        auth()
            ->user()
            ->conversations()
            ->updateExistingPivot($this->conversationId, [
                'last_read_at' => now(),
            ]);
    }

    #[On('message-received')]
    public function refreshMessages()
    {
        // just re-render
    }

    public function hydrate()
    {
        $this->markAsRead();
    }

    public function deleteMessage($messageId)
    {
        $message = Message::findOrFail($messageId);

        $userId = auth()->id();

        // safety: must belong to this conversation
        if ($message->conversation_id != $this->conversationId) {
            return;
        }

        if ($message->sender_id === $userId) {
            $message->update([
                'deleted_by_sender_at' => now(),
            ]);
        } else {
            $message->update([
                'deleted_by_receiver_at' => now(),
            ]);
        }
    }

    public function blockUser($userId)
    {
        auth()
            ->user()
            ->blockedUsers()
            ->syncWithoutDetaching([$userId]);
    }

    public function getOtherUserId()
    {
        return Conversation::find($this->conversationId)
            ->participants()
            ->where('user_id', '!=', auth()->id())
            ->value('user_id');
    }

    public function getIsBlockedProperty()
    {
        $otherUserId = $this->getOtherUserId();

        return auth()->user()->isBlockingOrBlocked($otherUserId);
    }

    public function unblockUser($userId)
    {
        auth()->user()->blockedUsers()->detach($userId);
    }

    public function getHasBlockedProperty()
    {
        $otherUserId = $this->getOtherUserId();

        return auth()->user()->hasBlocked($otherUserId);
    }

    public function getIsBlockedByOtherProperty()
    {
        $otherUserId = $this->getOtherUserId();

        return auth()->user()->isBlockedBy($otherUserId);
    }

    public function getCannotSendMessagesProperty()
    {
        $otherUserId = $this->getOtherUserId();

        return auth()->user()->hasBlocked($otherUserId) || auth()->user()->isBlockedBy($otherUserId);
    }
}; ?>

<div class="flex flex-col h-full min-h-0" x-data="{
    conversationId: @js($conversationId),
    userId: @js(auth()->id()),
    messages: @js($this->messages->values()),

    newMessage: '',

    channel: null,

    typing: false,
    typingTimeout: null,

    otherTyping: false,
    otherTypingTimeout: null,

    init() {
        this.subscribe();
    },

    subscribe() {
        if (!this.conversationId) return;

        this.channel = Echo.private('conversation.' + this.conversationId)
            .listen('.message.sent', (e) => {
                const msg = e.message;

                if (!msg || !msg.id) return;

                // 🔥 prevent duplicates (important)
                if (this.messages.find(m => m.id === msg.id)) return;

                // 🔥 replace temp message (optional but better)
                this.messages = this.messages.filter(m => m.id !== msg.temp_id);

                console.log('🔥 push', msg);

                this.messages.push(msg);
                this.scrollToBottom();

                // 👇 ADD THIS
                this.$wire.dispatch('message-received', {
                    conversationId: msg.conversation_id
                });
            })
            .listenForWhisper('typing', (e) => {
                if (e.userId === this.userId) return;

                this.otherTyping = true;

                clearTimeout(this.otherTypingTimeout);

                this.otherTypingTimeout = setTimeout(() => {
                    this.otherTyping = false;
                }, 1500);
            });
    },

    scrollToBottom() {
        this.$nextTick(() => {
            this.$refs.container.scrollTop = this.$refs.container.scrollHeight;
        });
    },

    notifyTyping() {
        if (!this.channel) return;

        this.channel.whisper('typing', {
            userId: this.userId
        });

        // optional debounce flag
        this.typing = true;

        clearTimeout(this.typingTimeout);

        this.typingTimeout = setTimeout(() => {
            this.typing = false;
        }, 1000);
    },

    sendLocalMessage() {
        if (!this.newMessage) return;

        const tempId = Date.now(); // temporary unique id

        this.messages.push({
            id: tempId,
            body: this.newMessage,
            sender_id: this.userId,
        });

        this.scrollToBottom();

        this.newMessage = '';
    }
}" x-init="init()">

    @if ($this->hasBlocked)
        <div class="text-red-500 text-sm mb-2">
            {{ __('You cannot send messages in this conversation.') }}
        </div>
    @endif

    @if ($this->isBlockedByOther)
        <div class="text-red-500 text-sm mb-2">
            {{ __('You cannot reply to this conversation.') }}
        </div>
    @endif

    <div class="flex justify-end mb-5 size-auto">
        @if ($this->hasBlocked)
            <flux:button wire:click="unblockUser({{ $this->getOtherUserId() }})" size="xs">
                {{ __('Unblock user') }}
            </flux:button>
        @elseif (!$this->isBlockedByOther)
            <flux:button wire:click="blockUser({{ $this->getOtherUserId() }})" variant="danger" size="xs">
                {{ __('Block user') }}
            </flux:button>
        @endif
    </div>

    <!-- Messages -->
    <div x-ref="container" class="flex-1 overflow-y-auto mb-4 space-y-2 min-h-0" wire:ignore>
        <template x-for="msg in messages" :key="msg.id">
            <div class="flex items-center gap-2 pe-4"
                :class="msg.sender_id === userId ? 'justify-end' : 'justify-start'">

                <!-- message -->
                <div class="px-3 py-2 rounded-2xl max-w-xs"
                    :class="msg.sender_id === userId ?
                        'bg-secondary text-secondary-foreground' :
                        'bg-secondary-foreground text-secondary'"
                    x-text="msg.body">
                </div>

                <!-- delete button -->
                <button class="text-xs text-secondary-foreground hover:opacity-100"
                    x-on:click="$wire.deleteMessage(msg.id); messages = messages.filter(m => m.id !== msg.id)">
                    ✕
                </button>

            </div>
        </template>
    </div>

    <span x-show="otherTyping" class="text-foreground text-sm">
        {{ __('Someone is typing...') }}
    </span>

    <!-- Input -->
    <div class="flex gap-2">

        @if (!$this->cannotSendMessages)
            <flux:input x-model="newMessage" wire:model="message"
                x-on:keydown.enter.prevent="sendLocalMessage(); $wire.sendMessage()"
                x-on:input.debounce.300ms="notifyTyping()" class="flex-1" placeholder="{{ __('Type a message...') }}" />
        @else
            <div class="text-sm p-2 bg-surface text-surface-foreground/70 rounded-lg border border-border flex-1">
                {{ __('Messaging disabled') }}
            </div>
        @endif



        <flux:button x-on:click="sendLocalMessage()" wire:click="sendMessage">
            {{ __('Send') }}
        </flux:button>
    </div>

</div>
