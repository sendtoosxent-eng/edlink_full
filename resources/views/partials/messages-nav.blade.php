<a href="{{ route('messages.index') }}" wire:navigate title="Messages" class="nav-link {{ request()->routeIs('messages.*') ? 'active' : '' }} flex items-center space-x-3 rounded-lg px-3 py-2.5" :class="$store.ui.collapsed && 'justify-center space-x-0'">
    <x-heroicon-o-chat-bubble-left-right class="h-5 w-5 flex-shrink-0" />
    <span x-show="!$store.ui.collapsed" x-cloak>Messages</span>
</a>
