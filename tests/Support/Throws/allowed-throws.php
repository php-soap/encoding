<?php
declare(strict_types=1);

use Soap\Encoding\Test\Unit\Encoder\ObjectPropertyPresenceTest;

/**
 * Deliberate intermediate throws, per test and site. Each entry needs the reason it cannot be avoided.
 */
return [
    [
        'test' => ObjectPropertyPresenceTest::class.'::optional_property_preserves_absence_null_and_value#lazy object whose initializer throws',
        'throw' => RuntimeException::class,
        'via' => 'src/Encoder/ObjectEncoder.php -> VeeWee\\Reflecta\\Lens\\Lens->get',
        'throws' => 3,
        'reason' => 'The fixture is a lazy object whose initializer throws on every access the encoder lenses make to it, to prove that the encoder keeps writing xsi:nil for it.',
    ],
    [
        'test' => ObjectPropertyPresenceTest::class.'::optional_property_preserves_absence_null_and_value#lazy object whose initializer throws',
        'throw' => RuntimeException::class,
        'via' => 'src/Encoder/ObjectEncoder.php -> VeeWee\\Reflecta\\Reflect\\property_is_initialized',
        'throws' => 1,
        'reason' => 'The same lazy object throws again when the encoder checks whether the null note is absent, and property_is_initialized() counts it as present.',
    ],
];
