<?php

use Livewire\Component;
use App\Models\Conversation;
use Livewire\Attributes\On;

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
            ->with([
                'participants',
                'latestMessage.sender',
                'item',
                'messages' => function ($q) {
                    $q->select('id', 'conversation_id', 'sender_id', 'created_at');
                },
            ])
            ->latest('updated_at')
            ->get()
            ->map(function ($conversation) {
                $pivot = $conversation->participants->firstWhere('id', auth()->id())->pivot;

                $lastRead = $pivot->last_read_at;

                $conversation->unread_count = $conversation->messages
                    ->where('sender_id', '!=', auth()->id())
                    ->where('created_at', '>', $lastRead)
                    ->count();

                return $conversation;
            });
    }

    #[On('message-received')]
    public function updateUnread($conversationId)
    {
        if ($conversationId === $this->selectedConversationId) {
            return;
        }

        $this->dispatch('$refresh');
    }
}; ?>

<div class="max-sm:space-y-3 sm:grid grid-cols-3 gap-4 h-full text-foreground"> {{-- wire:poll.5s --}}

    <!-- Inbox list -->
    <div class="col-span-1 border border-border rounded-2xl p-4 overflow-y-auto">

        <h2 class="text-lg font-semibold mb-4">{{ __('Messages') }}</h2>

        @foreach ($this->conversations as $conversation)
            <div wire:click="selectConversation({{ $conversation->id }})"
                class="p-3 mb-2 rounded-xl cursor-pointer hover:bg-secondary
                       {{ $selectedConversationId === $conversation->id ? 'bg-surface' : '' }}">
                <div class="flex justify-between items-center">
                    <div class="text-sm font-medium">
                        {{ $conversation->item->title ?? 'Item' }}
                    </div>

                    @if ($conversation->unread_count > 0)
                        <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                            {{ $conversation->unread_count }}
                        </span>
                    @endif
                </div>

                <div class="text-xs text-foreground/50">
                    {{ $conversation->latestMessage?->body }}
                </div>
            </div>
        @endforeach

    </div>

    <!-- Chat panel -->
    <div class="col-span-2 border border-border rounded-2xl p-4 flex flex-col min-h-0">
        @if ($selectedConversationId)
            <livewire:chat :conversationId="$selectedConversationId" />
        @else
            <div class="text-foreground/50">{{ __('Select a conversation') }}</div>
        @endif
    </div>

</div>
