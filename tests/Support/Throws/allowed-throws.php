<?php
declare(strict_types=1);

use Soap\Encoding\Test\Unit\Encoder\ObjectEncoderCustomLensTest;
use Soap\Encoding\Test\Unit\Encoder\ObjectPropertyPresenceTest;
use VeeWee\Reflecta\ArrayAccess\Exception\ArrayAccessException;
use VeeWee\Reflecta\Reflect\Exception\UnreflectableException;

/**
 * Deliberate intermediate throws, per test and site. Each entry needs the reason it cannot be avoided.
 */
return [
    [
        'test' => ObjectEncoderCustomLensTest::class.'::test_it_decodes_through_custom_lenses_that_throw_on_a_missing_element',
        'throw' => ArrayAccessException::class,
        'via' => 'src/Encoder/ObjectEncoder.php -> VeeWee\\Reflecta\\Lens\\Lens->get',
        'throws' => 1,
        'reason' => 'The fixture is a third-party encoder whose decoder lens throws on a missing element, to prove custom lenses still get the optional() and runLens treatment.',
    ],
    [
        'test' => ObjectEncoderCustomLensTest::class.'::test_it_encodes_through_custom_lenses_that_throw_on_a_missing_property',
        'throw' => UnreflectableException::class,
        'via' => 'src/Encoder/ObjectEncoder.php -> VeeWee\\Reflecta\\Lens\\Lens->get',
        'throws' => 1,
        'reason' => 'The fixture is a third-party encoder whose encoder lens throws on a missing property, to prove custom lenses still get the optional() and runLens treatment.',
    ],
    [
        'test' => ObjectPropertyPresenceTest::class.'::custom_lens_reading_null_keeps_writing_nil#lens throws on the absent property',
        'throw' => UnreflectableException::class,
        'via' => 'src/Encoder/ObjectEncoder.php -> VeeWee\\Reflecta\\Lens\\Lens->get',
        'throws' => 1,
        'reason' => 'The fixture is a third-party encoder whose encoder lens throws on a missing property, to prove that a failing custom lens keeps writing xsi:nil instead of counting as absent.',
    ],
    [
        'test' => ObjectPropertyPresenceTest::class.'::optional_property_preserves_absence_null_and_value#lazy object whose initializer throws',
        'throw' => RuntimeException::class,
        'via' => 'src/Encoder/ObjectEncoder.php -> VeeWee\\Reflecta\\Lens\\Lens->get',
        'throws' => 4,
        'reason' => 'The fixture is a lazy object whose initializer throws on every access the encoder lenses make to it, to prove that the encoder keeps writing xsi:nil for it.',
    ],
];
