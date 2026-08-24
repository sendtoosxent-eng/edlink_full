<?php

use App\Models\User;
use Database\Seeders\TeacherSubjectVisibilitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('offers every configured demo role from the landing page', function () {
    $response = $this->get(route('home'))->assertOk()->assertSee('Choose whose workspace you want to explore');

    foreach (config('edlink.demo.roles') as $role => $account) {
        $response->assertSee($account['label'])
            ->assertSee(route('login', ['demo' => $role]), false);
    }
});

it('keeps demo roles available when deployed configuration is stale', function () {
    Config::set('edlink.demo', null);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('School Administrator')
        ->assertSee(route('login', ['demo' => 'parent']), false);

    $this->get(route('login', ['demo' => 'parent']))
        ->assertOk()
        ->assertSee('Parent demo selected')
        ->assertSee('parent@edlink.local');
});

it('prefills only allowlisted demo credentials', function () {
    $this->get(route('login', ['demo' => 'bursar']))
        ->assertOk()
        ->assertSee('Bursar demo selected')
        ->assertSee(config('edlink.demo.school_number'))
        ->assertSee(config('edlink.demo.roles.bursar.email'));

    $this->get(route('login', ['demo' => 'not-a-real-role']))
        ->assertOk()
        ->assertDontSee('demo selected');
});

it('seeds working login accounts for every landing-page demo role', function () {
    $this->seed(TeacherSubjectVisibilitySeeder::class);

    foreach (config('edlink.demo.roles') as $role => $account) {
        expect(User::where('school_id', fn ($query) => $query
            ->select('id')->from('schools')->where('school_number', config('edlink.demo.school_number')))
            ->where('email', $account['email'])->exists())->toBeTrue("Missing demo account for {$role}");
    }

    Volt::test('pages.auth.login')
        ->set('school_number', config('edlink.demo.school_number'))
        ->set('email', config('edlink.demo.roles.bursar.email'))
        ->set('password', config('edlink.demo.password'))
        ->call('login')
        ->assertRedirect(route('workbench.home', absolute: false));
});

it('bypasses OTP verification for allowlisted demo accounts', function () {
    Notification::fake();
    Config::set('app.otp_force', true);
    $this->seed(TeacherSubjectVisibilitySeeder::class);

    Volt::test('pages.auth.login')
        ->set('school_number', config('edlink.demo.school_number'))
        ->set('email', config('edlink.demo.roles.administrator.email'))
        ->set('password', config('edlink.demo.password'))
        ->call('login')
        ->assertRedirect(route('dashboard', absolute: false));

    expect(session('otp_pending_user_id'))->toBeNull();
    Notification::assertNothingSent();
});

it('logs the parent demo into a linked learner dashboard', function () {
    $this->seed(TeacherSubjectVisibilitySeeder::class);

    Volt::test('pages.auth.login')
        ->set('school_number', config('edlink.demo.school_number'))
        ->set('email', config('edlink.demo.roles.parent.email'))
        ->set('password', config('edlink.demo.password'))
        ->call('login')
        ->assertRedirect(route('portal.home', absolute: false));

    $this->get(route('portal.home'))
        ->assertOk()
        ->assertSee('Parent / guardian')
        ->assertSee('Amina Class Learner')
        ->assertDontSee('No learner is linked');
});

it('opens the correct landing page after every demo role login', function (string $role, string $expectedRoute) {
    $this->seed(TeacherSubjectVisibilitySeeder::class);
    $account = config("edlink.demo.roles.{$role}");

    Volt::test('pages.auth.login')
        ->set('school_number', config('edlink.demo.school_number'))
        ->set('email', $account['email'])
        ->set('password', config('edlink.demo.password'))
        ->call('login')
        ->assertRedirect(route($expectedRoute, absolute: false));

    $this->get(route($expectedRoute))->assertOk();
})->with([
    'administrator' => ['administrator', 'dashboard'],
    'class teacher' => ['class-teacher', 'workbench.home'],
    'subject teacher' => ['subject-teacher', 'workbench.home'],
    'bursar' => ['bursar', 'workbench.home'],
    'parent' => ['parent', 'portal.home'],
    'student' => ['student', 'portal.home'],
]);

it('redirects portal accounts away from the staff workbench', function (string $role) {
    $this->seed(TeacherSubjectVisibilitySeeder::class);
    $user = User::where('email', config("edlink.demo.roles.{$role}.email"))->firstOrFail();

    $this->actingAs($user)->get(route('workbench.home'))->assertRedirect(route('portal.home'));
})->with(['parent', 'student']);
