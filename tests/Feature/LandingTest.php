<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_demo_notes_without_links(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Birthday present for Noor');
        $response->assertSee('data-emojis=', false);
        $response->assertDontSee('data-cy="note-list-item"', false);
        $response->assertDontSee('/notes/', false);
    }

    public function test_only_the_official_instance_says_where_it_is_hosted(): void
    {
        config(['app.official_instance' => false]);

        $this->get('/')->assertDontSee('Hosted in the Netherlands');

        config(['app.official_instance' => true]);

        $this->get('/')->assertSee('Hosted in the Netherlands (EU).');
    }

    public function test_logged_in_users_go_straight_to_their_notes(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/')->assertRedirect(route('notes.show'));
    }
}
