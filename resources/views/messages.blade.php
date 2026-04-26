<x-layouts::app :title="__('Messages')">
    <div class="max-w-6xl mx-auto py-6 h-[80vh]">
        <livewire:inbox :selectedConversationId="$conversationId" />
    </div>
</x-layouts::app>
