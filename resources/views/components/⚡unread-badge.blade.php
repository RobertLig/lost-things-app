<?php

use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component {
    protected $listeners = ['conversationSelected' => 'markAsRead'];

    public $count = 0;

    public function mount()
    {
        $this->calculate();
    }

    #[On('message-received')]
    public function refreshBadge()
    {
        $this->calculate();
    }

    public function calculate()
    {
        $this->count = auth()
            ->user()
            ->conversations->sum(function ($conversation) {
                $lastRead = $conversation->pivot->last_read_at;

                return $conversation->messages
                    ->where('sender_id', '!=', auth()->id())
                    ->where('created_at', '>', $lastRead)
                    ->count();
            });
    }

    public function markAsRead($id)
    {
        if (!$id) {
            return;
        }

        auth()
            ->user()
            ->conversations()
            ->updateExistingPivot($id, [
                'last_read_at' => now(),
            ]);

        $this->calculate();
    }
};
?>

<span>
    {{ $count > 0 ? $count : '' }}
</span>
