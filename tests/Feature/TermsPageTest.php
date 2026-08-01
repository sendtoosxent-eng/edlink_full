<?php

it('serves the public Edlink terms page', function () {
    $this->get(route('terms'))
        ->assertOk()
        ->assertSee('Terms and Conditions')
        ->assertSee('23 July 2026')
        ->assertSee('Service Description')
        ->assertSee('Limitation of Liability')
        ->assertSee('Spotnet Technologies');
});

it('links the landing-page footer to the terms page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('terms').'"', false);
});
