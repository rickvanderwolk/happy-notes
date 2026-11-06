<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class ProfileExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param list<string> $emojis
     */
    private function noteFor(User $user, string $title, ?string $body = null, array $emojis = ['🍕']): Note
    {
        $note = new Note(['title' => $title, 'body' => $body, 'emojis' => $emojis]);
        $note->user_id = $user->id;
        $note->save();

        return $note;
    }

    private function export(User $user, string $format): TestResponse
    {
        return $this->actingAs($user)->get(route('user.export.notes.create', ['format' => $format]));
    }

    /**
     * @return list<list<string|null>>
     */
    private function csvRows(string $content): array
    {
        $stream = fopen('php://temp', 'r+');
        $this->assertNotFalse($stream);
        fwrite($stream, $content);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream, null, ',', '"', '\\')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    public function test_the_export_only_contains_notes_of_the_signed_in_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->noteFor($owner, 'Mine');
        $theirs = $this->noteFor($other, 'Theirs');

        $json = $this->export($owner, 'json')->assertOk()->json();
        $titles = array_column($json, 'title');
        $this->assertContains('Mine', $titles);
        $this->assertNotContains('Theirs', $titles);
        $this->assertNotContains($theirs->uuid, array_column($json, 'uuid'));

        $csv = $this->export($owner, 'csv')->assertOk()->getContent();
        $this->assertIsString($csv);
        $this->assertStringContainsString('Mine', $csv);
        $this->assertStringNotContainsString('Theirs', $csv);
        $this->assertStringNotContainsString($theirs->uuid, $csv);
    }

    public function test_a_guest_is_redirected_to_login_without_data(): void
    {
        $owner = User::factory()->create();
        $this->noteFor($owner, 'Private');

        foreach (['json', 'csv'] as $format) {
            $response = $this->get(route('user.export.notes.create', ['format' => $format]))
                ->assertRedirect(route('login'));

            $this->assertFalse($response->headers->has('Content-Disposition'));
            $this->assertStringNotContainsString('Private', (string) $response->getContent());
        }
    }

    public function test_the_json_export_is_complete(): void
    {
        $user = User::factory()->create();
        $body = '{"time":1,"blocks":[{"id":"a","type":"paragraph","data":{"text":"Hello"}}]}';
        $note = $this->noteFor($user, 'With body', $body, ['🍕', '🌮']);
        $this->noteFor($user, 'Without body');

        $response = $this->export($user, 'json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json');
        $json = $response->json();

        $this->assertCount(Note::where('user_id', $user->id)->count(), $json);
        $this->assertCount(3, $json);

        foreach ($json as $row) {
            $this->assertArrayHasKey('uuid', $row);
            $this->assertArrayHasKey('title', $row);
            $this->assertArrayHasKey('body', $row);
            $this->assertArrayHasKey('emojis', $row);
        }

        $exported = collect($json)->firstWhere('uuid', $note->uuid);
        $this->assertIsArray($exported);
        $this->assertSame('With body', $exported['title']);
        $this->assertIsArray($exported['body']);
        $this->assertSame(json_decode($body, true), $exported['body']);
        $this->assertSame(['🍕', '🌮'], $exported['emojis']);

        $welcome = collect($json)->firstWhere('title', User::welcomeNoteTitle());
        $this->assertIsArray($welcome);
        $this->assertIsArray($welcome['body']);
    }

    public function test_the_csv_export_has_a_header_and_one_row_per_note(): void
    {
        $user = User::factory()->create();
        $this->noteFor($user, 'First', '{"blocks":[]}');
        $this->noteFor($user, 'Second');

        $response = $this->export($user, 'csv')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertMatchesRegularExpression('/^attachment; filename="happynotes_export_\d{4}-\d{2}-\d{2}_\d{6}\.csv"$/', $disposition);

        $rows = $this->csvRows((string) $response->getContent());
        $this->assertSame(['uuid', 'title', 'body', 'emojis', 'created_at', 'updated_at'], $rows[0]);
        $this->assertCount(1 + Note::where('user_id', $user->id)->count(), $rows);
        $this->assertCount(4, $rows);

        $titles = array_column(array_slice($rows, 1), 1);
        $this->assertContains('First', $titles);
        $this->assertContains('Second', $titles);
        $this->assertContains(User::welcomeNoteTitle(), $titles);
    }

    public function test_csv_cells_that_look_like_formulas_are_prefixed_with_a_quote(): void
    {
        $user = User::factory()->create();
        $titles = ['=SUM(A1:A2)', '+31612345678', '-2+3', '@cmd', 'Plain'];
        foreach ($titles as $title) {
            $this->noteFor($user, $title);
        }

        $rows = $this->csvRows((string) $this->export($user, 'csv')->assertOk()->getContent());
        $exported = array_column(array_slice($rows, 1), 1);

        $this->assertContains("'=SUM(A1:A2)", $exported);
        $this->assertContains("'+31612345678", $exported);
        $this->assertContains("'-2+3", $exported);
        $this->assertContains("'@cmd", $exported);
        $this->assertContains('Plain', $exported);
        $this->assertNotContains('=SUM(A1:A2)', $exported);
        $this->assertNotContains('+31612345678', $exported);
        $this->assertNotContains('-2+3', $exported);
        $this->assertNotContains('@cmd', $exported);
    }

    public function test_an_unknown_format_gives_a_400(): void
    {
        $user = User::factory()->create();
        $this->noteFor($user, 'Mine');

        $response = $this->export($user, 'xml')
            ->assertStatus(400)
            ->assertJson(['error' => 'Invalid format']);

        $this->assertStringNotContainsString('Mine', (string) $response->getContent());
    }
}
