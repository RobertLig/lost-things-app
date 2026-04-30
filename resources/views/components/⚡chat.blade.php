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

        $message = Message::create([
            'conversation_id' => $this->conversationId,
            'sender_id' => auth()->id(),
            'body' => $this->message,
        ]);

        broadcast(new MessageSent($message)); //->toOthers()

        // update conversation timestamp (important for sorting)
        Conversation::where('id', $this->conversationId)->update(['updated_at' => now()]);

        $this->reset('message');
    }

    public function getMessagesProperty()
    {
        if (!$this->conversationId) {
            return collect();
        }

        return Message::query()->where('conversation_id', $this->conversationId)->with('sender')->latest()->take(50)->get()->reverse();
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
}; ?>

<div class="flex flex-col h-full" x-data="{
    conversationId: @js($conversationId),
    userId: @js(auth()->id()),
    messages: @js($this->messages->values()),

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

                if (this.messages.find(m => m.id === msg.id)) return;

                console.log('🔥 push', msg);

                this.messages.push(msg);
                this.scrollToBottom();
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
    }
}" x-init="init()">

    <!-- Messages -->
    <div x-ref="container" class="flex-1 overflow-y-auto mb-4 space-y-2" wire:ignore>
        <template x-for="msg in messages" :key="msg.id">
            <div class="flex" :class="msg.sender_id === userId ? 'justify-end' : 'justify-start'">
                <div class="px-3 py-2 rounded-2xl max-w-xs"
                    :class="msg.sender_id === userId ?
                        'bg-secondary text-secondary-foreground' :
                        'bg-secondary-foreground text-secondary'"
                    x-text="msg.body"></div>
            </div>
        </template>
    </div>

    <span x-show="otherTyping" class="text-foreground text-sm">
        {{ __('Someone is typing...') }}
    </span>

    <!-- Input -->
    <div class="flex gap-2">
        <flux:input wire:model="message" wire:keydown.enter="sendMessage" x-on:input.debounce.300ms="notifyTyping()"
            class="flex-1" placeholder="{{ __('Type a message...') }}" />

        <flux:button wire:click="sendMessage">
            {{ __('Send') }}
        </flux:button>
    </div>

</div>
