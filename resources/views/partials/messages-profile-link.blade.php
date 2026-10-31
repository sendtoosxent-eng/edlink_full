<a href="{{ route('messages.index') }}" wire:navigate class="flex items-center space-x-3 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50">
    <x-heroicon-o-chat-bubble-left-right class="h-4 w-4" />
    <span>Messages</span>
    <livewire:message-unread-count />
</a>
