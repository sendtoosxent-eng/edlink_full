<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Support\ChatAccess;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class MessageUnreadCount extends Component
{
    public function render()
    {
        $count = 0;
        if (Schema::hasTable('chat_messages')) {
            $user = ChatAccess::actor();
            $count = ChatMessage::where('school_id', $user->school_id)->where('recipient_id', $user->id)->whereNull('read_at')->count();
        }

        return view('livewire.message-unread-count', compact('count'));
    }
}
