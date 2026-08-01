<?php

it('serves the public Edlink privacy policy page', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee('23 July 2026')
        ->assertSee('Information We Collect')
        ->assertSeeText('Children\'s Data')
        ->assertSee('contact@edlink.com');
});

it('links the landing-page footer to the privacy policy page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('privacy').'"', false);
});
