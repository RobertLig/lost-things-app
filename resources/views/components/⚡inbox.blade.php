<?php

use Livewire\Component;
use App\Models\Conversation;

new class extends Component {
    public $selectedConversationId = null;

    public function mount($selectedConversationId = null)
    {
        $this->selectedConversationId = $selectedConversationId;

        if (!$this->selectedConversationId) {
            $first = $this->conversations->first();
            $this->selectedConversationId = $first?->id;
        }
    }

    public function selectConversation($id)
    {
        $this->selectedConversationId = $id;

        $this->dispatch('conversationSelected', id: $id);
    }

    public function getConversationsProperty()
    {
        return Conversation::query()
            ->whereHas('participants', fn($q) => $q->where('user_id', auth()->id()))
            ->with(['participants', 'latestMessage.sender', 'item'])
            ->latest('updated_at')
            ->get();
    }
}; ?>

<div class="grid grid-cols-3 gap-4 h-full text-foreground">

    <!-- Inbox list -->
    <div class="col-span-1 border border-border rounded-2xl p-4 overflow-y-auto">

        <h2 class="text-lg font-semibold mb-4">{{ __('Messages') }}</h2>

        @foreach ($this->conversations as $conversation)
            <div wire:click="selectConversation({{ $conversation->id }})"
                class="p-3 mb-2 rounded-xl cursor-pointer hover:bg-secondary
                       {{ $selectedConversationId === $conversation->id ? 'bg-surface' : '' }}">
                <div class="text-sm font-medium">
                    {{ $conversation->item->title ?? __('Item') }}
                </div>

                <div class="text-xs text-foreground/50">
                    {{ $conversation->latestMessage?->body }}
                </div>
            </div>
        @endforeach

    </div>

    <!-- Chat panel -->
    <div class="col-span-2 border border-border rounded-2xl p-4">
        @if ($selectedConversationId)
            <livewire:chat :conversationId="$selectedConversationId" />
        @else
            <div class="text-foreground/50">{{ __('Select a conversation') }}</div>
        @endif
    </div>

</div>
