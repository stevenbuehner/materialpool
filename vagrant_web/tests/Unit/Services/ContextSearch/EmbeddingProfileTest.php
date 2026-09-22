<?php

namespace Tests\Unit\Services\ContextSearch;

use App\Services\ContextSearch\EmbeddingProfile;
use InvalidArgumentException;
use Tests\TestCase;

final class EmbeddingProfileTest extends TestCase
{
    public function test_profile_id_is_stable_for_equivalent_options(): void
    {
        $first = EmbeddingProfile::fromConfiguration([
            'model' => 'embeddinggemma:300m-qat-q8_0',
            'digest' => str_repeat('a', 64),
            'dimensions' => 768,
            'options_json' => '{"seed": 7, "nested": {"b": 2, "a": 1}}',
        ]);
        $second = EmbeddingProfile::fromConfiguration([
            'model' => 'embeddinggemma:300m-qat-q8_0',
            'digest' => str_repeat('a', 64),
            'dimensions' => 768,
            'options_json' => '{"nested": {"a": 1, "b": 2}, "seed": 7}',
        ]);

        $this->assertSame($first->id(), $second->id());
    }

    public function test_profile_requires_a_pinned_digest(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('digest');

        EmbeddingProfile::fromConfiguration([
            'model' => 'embeddinggemma:300m-qat-q8_0',
            'digest' => '',
            'dimensions' => 768,
            'options_json' => '{}',
        ]);
    }
}
