<?php

use App\Livewire\Messages;
use App\Livewire\MessageUnreadCount;
use App\Models\ChatMessage;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::create(['name' => 'Chat School', 'slug' => 'chat-school', 'status' => 'active', 'license_status' => 'active']);
    $this->sender = User::factory()->create(['name' => 'Alice Teacher', 'school_id' => $this->school->id, 'role' => 'teacher']);
    $this->recipient = User::factory()->create(['name' => 'Ben Teacher', 'school_id' => $this->school->id, 'role' => 'teacher']);
    RateLimiter::clear('chat-send:'.$this->sender->id);
});

function insertChatMessage(User $sender, User $recipient, string $body = 'Hello from your school'): ChatMessage
{
    return ChatMessage::create(['school_id' => $sender->school_id, 'sender_id' => $sender->id,
        'recipient_id' => $recipient->id, 'body' => $body, 'client_token' => (string) Str::uuid()]);
}

it('requires login and exposes Messages in the dashboard and module navigation', function () {
    $this->get(route('messages.index'))->assertRedirect(route('login'));
    $this->actingAs($this->sender)->get(route('messages.index'))->assertOk()
        ->assertSee('Messages')->assertSee('Ben Teacher')->assertSee('wire:poll.2s.visible', false);
    $admin = User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin']);
    $this->actingAs($admin)->get(route('dashboard'))->assertOk()
        ->assertSee(route('messages.index'), false)->assertSee('message-unread-count', false);
    $this->actingAs($this->sender)->get(route('workbench.home'))->assertOk()->assertSee(route('messages.index'), false);
});

it('delivers messages on refresh and records read receipts only for the recipient', function () {
    $sender = Livewire::actingAs($this->sender)->test(Messages::class)
        ->call('openConversation', $this->recipient->id)->set('body', '  Hello Ben!  ')->call('send')
        ->assertHasNoErrors()->assertSet('body', '')->assertSee('Hello Ben!')->assertSee('Sent');
    $message = ChatMessage::firstOrFail();
    expect($message->body)->toBe('Hello Ben!')->and($message->read_at)->toBeNull();
    Livewire::actingAs($this->recipient)->test(MessageUnreadCount::class)->assertSee('1 unread messages');
    $recipient = Livewire::actingAs($this->recipient)->test(Messages::class)->call('openConversation', $this->sender->id)
        ->assertSee('Hello Ben!');
    expect($message->fresh()->read_at)->not->toBeNull();
    Livewire::actingAs($this->recipient)->test(MessageUnreadCount::class)->assertDontSee('1 unread messages');
    insertChatMessage($this->sender, $this->recipient, 'A second message');
    $recipient->call('$refresh')->assertSee('A second message');
    $this->actingAs($this->sender);
    $sender->call('$refresh')->assertSee('Read');
});

it('hides other users conversations and rejects cross-school or self recipients', function () {
    $other = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    insertChatMessage($this->recipient, $other, 'Confidential note for someone else');
    Livewire::actingAs($this->sender)->test(Messages::class)->call('openConversation', $this->recipient->id)
        ->assertDontSee('Confidential note for someone else');
    $foreignSchool = School::create(['name' => 'Another School', 'slug' => 'another-school', 'license_status' => 'active']);
    $foreign = User::factory()->create(['name' => 'Foreign Person', 'school_id' => $foreignSchool->id, 'role' => 'teacher']);
    Livewire::actingAs($this->sender)->test(Messages::class)->assertDontSee('Foreign Person')
        ->call('openConversation', $foreign->id)->assertNotFound();
    Livewire::actingAs($this->sender)->test(Messages::class)->call('openConversation', $this->sender->id)->assertNotFound();
});

it('validates messages and escapes HTML', function () {
    $chat = Livewire::actingAs($this->sender)->test(Messages::class)->call('openConversation', $this->recipient->id);
    $chat->set('body', '   ')->call('send')->assertHasErrors(['body' => 'required']);
    $chat->set('body', str_repeat('x', 5001))->call('send')->assertHasErrors(['body' => 'max']);
    $chat->set('body', '<script>alert(1)</script>')->call('send')->assertHasNoErrors()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    expect(ChatMessage::count())->toBe(1);
});

it('rejects inactive recipients and users deactivated after opening the screen', function () {
    $chat = Livewire::actingAs($this->sender)->test(Messages::class)->call('openConversation', $this->recipient->id)->set('body', 'Cannot send');
    $this->recipient->update(['employment_status' => 'inactive']);
    $chat->call('send')->assertNotFound();
    $this->recipient->update(['employment_status' => 'active']);
    $chat = Livewire::actingAs($this->sender)->test(Messages::class)->call('openConversation', $this->recipient->id)->set('body', 'Cannot send');
    $this->sender->update(['employment_status' => 'inactive']);
    $chat->call('send')->assertForbidden();
    expect(ChatMessage::count())->toBe(0);
});

it('limits rapid sending', function () {
    $chat = Livewire::actingAs($this->sender)->test(Messages::class)->call('openConversation', $this->recipient->id);
    for ($i = 0; $i < 30; $i++) {
        RateLimiter::hit('chat-send:'.$this->sender->id, 60);
    }
    $chat->set('body', 'Too fast')->call('send')->assertHasErrors('body');
    expect(ChatMessage::count())->toBe(0);
});

it('does not duplicate a message when its send token is retried', function () {
    $chat = Livewire::actingAs($this->sender)->test(Messages::class)->call('openConversation', $this->recipient->id)
        ->set('body', 'Only once');
    ChatMessage::create(['school_id' => $this->school->id, 'sender_id' => $this->sender->id,
        'recipient_id' => $this->recipient->id, 'body' => 'Only once', 'client_token' => $chat->get('clientToken')]);
    $chat->call('send')->assertHasNoErrors()->assertSet('body', '');
    expect(ChatMessage::count())->toBe(1);
});

it('loads older history and never marks unrelated conversations read', function () {
    $other = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    $unrelated = insertChatMessage($other, $this->recipient, 'Unrelated message');
    for ($i = 0; $i < 55; $i++) {
        insertChatMessage($this->sender, $this->recipient, 'Message number '.$i.'.');
    }
    Livewire::actingAs($this->recipient)->test(Messages::class)->call('openConversation', $this->sender->id)
        ->assertSee('Message number 54.')->assertDontSee('Message number 0.')
        ->call('loadOlder')->assertSee('Message number 0.');
    expect($unrelated->fresh()->read_at)->toBeNull();
});

it('keeps branch conversations in their active school and rejects switching an open chat', function () {
    $branch = School::create(['name' => 'Branch School', 'slug' => 'branch-school', 'license_status' => 'active']);
    $branchUser = User::factory()->create(['school_id' => $branch->id, 'role' => 'teacher']);
    $this->sender->schoolAccesses()->attach($branch->id, ['role' => 'teacher']);
    $this->withSession(['active_school_id' => $branch->id]);
    $chat = Livewire::actingAs($this->sender)->test(Messages::class)->call('openConversation', $branchUser->id)
        ->set('body', 'Branch conversation')->call('send')->assertHasNoErrors();
    expect(ChatMessage::firstOrFail()->school_id)->toBe($branch->id);
    Livewire::actingAs($branchUser)->test(Messages::class)->assertSee('Alice Teacher')
        ->call('openConversation', $this->sender->id)->assertSee('Branch conversation');
    $this->actingAs($this->sender);
    $chat->set('body', 'Wrong school');
    $this->withSession(['active_school_id' => $this->school->id]);
    $chat->call('send')->assertForbidden();
});

it('allows parent and learner chats only while linked learners are active', function () {
    $learner = Student::create(['school_id' => $this->school->id, 'name' => 'Chat Learner', 'admission_no' => 'CHAT-1', 'status' => 'active']);
    $parent = User::factory()->create(['name' => 'Chat Parent', 'school_id' => $this->school->id, 'role' => 'parent']);
    $student = User::factory()->create(['name' => 'Chat Student', 'school_id' => $this->school->id, 'role' => 'student']);
    foreach ([$parent, $student] as $user) {
        $user->portalStudents()->attach($learner->id, ['school_id' => $this->school->id, 'relationship' => 'learner']);
    }
    Livewire::actingAs($parent)->test(Messages::class)->call('openConversation', $this->sender->id)
        ->set('body', 'Parent question')->call('send')->assertHasNoErrors();
    Livewire::actingAs($student)->test(Messages::class)->call('openConversation', $this->sender->id)
        ->set('body', 'Learner question')->call('send')->assertHasNoErrors();
    Livewire::actingAs($this->sender)->test(Messages::class)->assertSee('Chat Parent')->assertSee('Chat Student');
    $learner->update(['status' => 'inactive']);
    Livewire::actingAs($this->sender)->test(Messages::class)->assertDontSee('Chat Parent')->assertDontSee('Chat Student');
    Livewire::actingAs($parent)->test(Messages::class)->assertForbidden();
});

it('prevents client-side changes to the conversation identity', function () {
    expect(fn () => Livewire::actingAs($this->sender)->test(Messages::class)->set('peerId', $this->recipient->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('rejects an account whose school licence expires after opening chat', function () {
    $chat = Livewire::actingAs($this->sender)->test(Messages::class)->call('openConversation', $this->recipient->id)
        ->set('body', 'Cannot send');
    $this->school->update(['license_status' => 'expired']);
    $chat->call('send')->assertForbidden();
    expect(ChatMessage::count())->toBe(0);
});
