<?php

use Livewire\Component;
use App\Models\Conversation;
use App\Models\Message;

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

        Message::create([
            'conversation_id' => $this->conversationId,
            'sender_id' => auth()->id(),
            'body' => $this->message,
        ]);

        // update conversation timestamp (important for sorting)
        Conversation::where('id', $this->conversationId)->update(['updated_at' => now()]);

        $this->reset('message');

        $this->dispatch('$refresh');
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
}; ?>

<div class="flex flex-col h-full">

    <!-- Messages -->
    <div class="flex-1 overflow-y-auto mb-4 space-y-2">

        @foreach ($this->messages as $msg)
            <div class="flex {{ $msg->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">

                <div
                    class="px-3 py-2 rounded-2xl max-w-xs
                    {{ $msg->sender_id === auth()->id() ? 'bg-secondary text-secondary-foreground' : 'bg-secondary-foreground text-secondary' }}">

                    {{ $msg->body }}
                </div>

            </div>
        @endforeach

    </div>

    <!-- Input -->
    <div class="flex gap-2">
        <flux:input wire:model="message" wire:keydown.enter="sendMessage" class="flex-1"
            placeholder="{{ __('Type a message...') }}" />

        <flux:button wire:click="sendMessage">
            {{ __('Send') }}
        </flux:button>
    </div>

</div>
