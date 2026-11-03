<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_about_page_shows_the_version_and_links(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/about')
            ->assertOk()
            ->assertSee((string) config('app.version'))
            ->assertSee('https://github.com/rickvanderwolk/happynotes', false);
    }

    public function test_the_official_instance_explains_it_is_the_hosted_version(): void
    {
        config(['app.official_instance' => true]);

        $this->actingAs(User::factory()->create())
            ->get('/about')
            ->assertSee('happynotes.cc is the free hosted version')
            ->assertDontSee('self-hosted copy');
    }

    public function test_a_self_hosted_copy_says_so(): void
    {
        config(['app.official_instance' => false]);

        $this->actingAs(User::factory()->create())
            ->get('/about')
            ->assertSee('self-hosted copy')
            ->assertDontSee('Hosted in the Netherlands');
    }

    public function test_the_menu_links_to_the_about_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/menu')
            ->assertSee(route('about.show'), false)
            ->assertDontSee('https://github.com/rickvanderwolk/happynotes', false);
    }

    public function test_guests_cannot_see_the_about_page(): void
    {
        $this->get('/about')->assertRedirect('/login');
    }
}
