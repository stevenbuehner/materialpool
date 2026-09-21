<?php

namespace Tests\Unit\Services\ContextSearch;

use App\Services\ContextSearch\TextChunker;
use InvalidArgumentException;
use Tests\TestCase;

class TextChunkerTest extends TestCase
{
    public function test_preserves_unicode_content_and_character_locators(): void
    {
        $text = "Äpfel gehören zum ersten Absatz. Er enthält zwei Sätze.\n\nDer zweite Absatz beschreibt die Suche im Materialpool.";

        $chunks = (new TextChunker(targetCharacters: 60, overlapCharacters: 20))->chunk($text);

        $this->assertCount(2, $chunks);
        $this->assertSame('Äpfel gehören zum ersten Absatz. Er enthält zwei Sätze.', $chunks[0]->content);
        $this->assertSame(0, $chunks[0]->startCharacter);
        $this->assertSame(55, $chunks[0]->endCharacter);
        $this->assertSame('Der zweite Absatz beschreibt die Suche im Materialpool.', $chunks[1]->content);
        $this->assertSame(57, $chunks[1]->startCharacter);
        $this->assertSame(112, $chunks[1]->endCharacter);
    }

    public function test_repeats_a_complete_boundary_unit_as_overlap(): void
    {
        $text = 'Erster Satz bleibt im Kontext. Zweiter Satz wird überlappt. Dritter Satz beendet den Text.';

        $chunks = (new TextChunker(targetCharacters: 70, overlapCharacters: 15))->chunk($text);

        $this->assertCount(2, $chunks);
        $this->assertSame('Erster Satz bleibt im Kontext. Zweiter Satz wird überlappt.', $chunks[0]->content);
        $this->assertSame('Zweiter Satz wird überlappt. Dritter Satz beendet den Text.', $chunks[1]->content);
        $this->assertSame(31, $chunks[1]->startCharacter);
    }

    public function test_rejects_an_overlap_that_is_not_smaller_than_the_target(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TextChunker(targetCharacters: 100, overlapCharacters: 100);
    }
}
