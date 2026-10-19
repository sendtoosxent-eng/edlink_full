<?php

use Carbon\Carbon;

it('serves the public Edlink privacy policy page', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee(Carbon::parse(config('edlink.legal.policy_version'))->format('d F Y'))
        ->assertSee('Information We Collect')
        ->assertSeeText('Children\'s Data')
        ->assertSee(config('edlink.legal.contact_email'));
});

it('links the landing-page footer to the privacy policy page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('privacy').'"', false);
});
