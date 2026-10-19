<?php

use Carbon\Carbon;

it('serves the public Edlink terms page', function () {
    $this->get(route('terms'))
        ->assertOk()
        ->assertSee('Terms and Conditions')
        ->assertSee(Carbon::parse(config('edlink.legal.policy_version'))->format('d F Y'))
        ->assertSee('Service Description')
        ->assertSee('Limitation of Liability')
        ->assertSee('Spotnet Technologies')
        ->assertSee(config('edlink.legal.contact_email'));
});

it('links the landing-page footer to the terms page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('terms').'"', false);
});
