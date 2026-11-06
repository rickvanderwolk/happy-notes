<?php

namespace Tests\Unit;

use App\Helpers\NoteBodyHelper;
use PHPUnit\Framework\TestCase;

final class NoteBodyHelperTest extends TestCase
{
    /**
     * @param list<array<string, mixed>> $blocks
     */
    private function body(array $blocks): string
    {
        return json_encode(['time' => 1, 'blocks' => $blocks, 'version' => '2.31.0'], JSON_THROW_ON_ERROR);
    }

    public function test_empty_bodies_have_no_text(): void
    {
        $this->assertNull(NoteBodyHelper::toPlainText(null));
        $this->assertNull(NoteBodyHelper::toPlainText(''));
        $this->assertNull(NoteBodyHelper::toPlainText($this->body([])));
        $this->assertNull(NoteBodyHelper::toPlainText($this->body([['type' => 'delimiter', 'data' => []]])));
    }

    public function test_every_block_type_ends_up_as_text_without_the_json_around_it(): void
    {
        $text = NoteBodyHelper::toPlainText($this->body([
            ['id' => 'a', 'type' => 'header', 'data' => ['text' => 'Heading', 'level' => 2]],
            ['id' => 'b', 'type' => 'paragraph', 'data' => ['text' => 'Paragraph']],
            ['id' => 'c', 'type' => 'list', 'data' => ['style' => 'unordered', 'meta' => [], 'items' => [
                ['content' => 'Item', 'meta' => [], 'items' => [
                    ['content' => 'Nested', 'meta' => [], 'items' => []],
                ]],
            ]]],
            ['id' => 'd', 'type' => 'list', 'data' => ['style' => 'ordered', 'items' => ['Old', 'Style']]],
            ['id' => 'e', 'type' => 'checklist', 'data' => ['items' => [['text' => 'Todo', 'checked' => true]]]],
            ['id' => 'f', 'type' => 'table', 'data' => ['withHeadings' => false, 'content' => [['Cell', 'Other'], ['Row', 'Two']]]],
            ['id' => 'g', 'type' => 'code', 'data' => ['code' => 'echo 1;']],
            ['id' => 'h', 'type' => 'delimiter', 'data' => []],
        ]));

        $this->assertSame('Heading Paragraph Item Nested Old Style Todo Cell Other Row Two echo 1;', $text);
    }

    public function test_html_and_entities_are_turned_into_plain_text(): void
    {
        $text = NoteBodyHelper::toPlainText($this->body([
            ['type' => 'paragraph', 'data' => ['text' => 'Hi&nbsp;👋 <b>bold</b><br>next &amp; <a href="https://example.com">link</a> &quot;quoted&quot;']],
        ]));

        $this->assertSame('Hi 👋 bold next & link "quoted"', $text);
    }

    public function test_a_body_that_is_not_json_is_used_as_text(): void
    {
        $this->assertSame('Plain body text', NoteBodyHelper::toPlainText('Plain <i>body</i> text'));
    }
}
