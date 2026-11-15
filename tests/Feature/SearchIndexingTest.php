<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchIndexingTest extends TestCase
{
    use RefreshDatabase;

    private const NOINDEX = '<meta name="robots" content="noindex, nofollow">';

    public function test_the_official_landing_page_can_be_indexed_and_describes_the_app(): void
    {
        config(['app.official_instance' => true]);

        $html = (string) $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString(self::NOINDEX, $html);
        $this->assertStringContainsString('<meta property="og:title" content="HappyNotes">', $html);

        if (preg_match('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $match) !== 1) {
            $this->fail('No structured data on the landing page.');
        }
        $data = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('WebApplication', $data['@type']);
        $this->assertSame('HappyNotes', $data['name']);
    }

    public function test_a_self_hosted_landing_page_stays_out_of_search_results(): void
    {
        config(['app.official_instance' => false]);

        $this->get('/')->assertOk()->assertSee(self::NOINDEX, false);
    }

    public function test_auth_pages_stay_out_of_search_results(): void
    {
        config(['app.official_instance' => true]);

        $this->get('/login')->assertOk()->assertSee(self::NOINDEX, false);
        $this->get('/register')->assertOk()->assertSee(self::NOINDEX, false);
    }

    public function test_pages_behind_the_login_stay_out_of_search_results(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/notes')->assertOk()->assertSee(self::NOINDEX, false);
        $this->get('/user/profile')->assertOk()->assertSee(self::NOINDEX, false);
    }
}
