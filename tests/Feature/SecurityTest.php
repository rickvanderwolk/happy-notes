<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\TestController;
use App\Models\Note;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\RouteCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

final class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function signedInPages(): array
    {
        return [
            'notes' => ['/notes'],
            'new note' => ['/notes/new'],
            'filter' => ['/filter'],
            'exclude filter' => ['/filter/exclude'],
            'search' => ['/filter/search'],
            'menu' => ['/menu'],
            'insights' => ['/insights'],
            'shortcuts' => ['/shortcuts'],
            'cleanup' => ['/cleanup'],
            'export' => ['/user/export'],
            'profile' => ['/user/profile'],
        ];
    }

    #[DataProvider('signedInPages')]
    public function test_guests_are_sent_to_login(string $path): void
    {
        $this->get($path)->assertRedirect(route('login'));
    }

    public function test_guests_cannot_store_anything(): void
    {
        $owner = User::factory()->create();
        $note = new Note(['title' => 'Mine', 'emojis' => ['🍕']]);
        $note->user_id = $owner->id;
        $note->save();

        $this->post(route('note.store'), ['title' => 'New 🍕'])->assertRedirect(route('login'));
        $this->post(route('note.title.store', $note->uuid), ['title' => 'Taken'])->assertRedirect(route('login'));
        $this->post(route('note.body.store', $note->uuid), ['body' => ['blocks' => []]])->assertRedirect(route('login'));
        $this->delete(route('note.destroy', $note->uuid))->assertRedirect(route('login'));
        $this->postJson(route('filter.emojis.store'), ['storageKey' => 'selected_emojis', 'emojis' => [], 'otherEmojis' => []])->assertUnauthorized();

        $this->assertSame(2, Note::withoutGlobalScopes()->where('user_id', $owner->id)->count());
        $this->assertSame('Mine', Note::withoutGlobalScopes()->find($note->id)?->title);
    }

    public function test_responses_carry_the_security_headers(): void
    {
        $response = $this->get('/login')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = (string) $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[^']+'/", $csp);
    }

    public function test_titles_and_bodies_are_escaped(): void
    {
        $user = User::factory()->create();
        $payload = '"><img src=x onerror=alert(1)></script><script>alert(2)</script>';
        $note = new Note([
            'title' => $payload,
            'emojis' => ['🍕'],
            'body' => json_encode(['blocks' => [['type' => 'paragraph', 'data' => ['text' => $payload]]]]),
        ]);
        $note->user_id = $user->id;
        $note->save();

        foreach ([route('notes.show'), route('note.show', $note->uuid), route('note.title.show', $note->uuid)] as $url) {
            $content = (string) $this->actingAs($user)->get($url)->assertOk()->getContent();

            $this->assertStringNotContainsString('<img src=x', $content, $url);
            $this->assertStringNotContainsString('<script>alert(2)', $content, $url);
        }
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        $this->app->offsetUnset(ThrottleRequests::class);
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertRedirect();
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertTooManyRequests();
        $this->assertGuest();
    }

    public function test_rate_limiting_stays_on_outside_local_and_testing(): void
    {
        $this->app->offsetUnset(ThrottleRequests::class);
        $this->app['env'] = 'production';

        (new AppServiceProvider($this->app))->boot();

        $this->assertInstanceOf(ThrottleRequests::class, $this->app->make(ThrottleRequests::class));
    }

    public function test_the_cypress_fixture_routes_do_not_exist_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';
        $router = $this->app['router'];
        $router->setRoutes(new RouteCollection());
        $router->prefix('api')->middleware('api')->group(base_path('routes/api.php'));

        $uris = collect($router->getRoutes()->getRoutes())->map(fn ($route) => $route->uri());
        $this->assertContains('api/user', $uris);
        $this->assertEmpty($uris->filter(fn (string $uri) => str_starts_with($uri, 'api/test')));
    }

    public function test_the_cypress_fixture_controller_refuses_to_run_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';

        foreach (['resetFilters', 'noteCount'] as $method) {
            try {
                (new TestController())->{$method}();
                $this->fail("{$method} ran in production");
            } catch (NotFoundHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
