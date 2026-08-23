<?php

use App\Models\User;
use Database\Seeders\TeacherSubjectVisibilitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('offers every configured demo role from the landing page', function () {
    $response = $this->get(route('home'))->assertOk()->assertSee('Choose whose workspace you want to explore');

    foreach (config('edlink.demo.roles') as $role => $account) {
        $response->assertSee($account['label'])
            ->assertSee(route('login', ['demo' => $role]), false);
    }
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
