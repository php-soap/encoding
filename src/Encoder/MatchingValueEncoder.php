<?php declare(strict_types=1);

namespace Soap\Encoding\Encoder;

use Closure;
use Soap\Encoding\Xml\Node\Element;
use VeeWee\Reflecta\Iso\Iso;
use function Psl\invariant;

/**
 * This encoder can be used to select an encoder based on the value being encoded.
 * For decoding, it will always use the default encoder.
 *
 * @psalm-type MatchedEncoderInfo = Context | array{0: Context, 1 ?: XmlEncoder<mixed, string>|null}
 * @psalm-type MatchingEncoderDetector = \Closure(Context, mixed): MatchedEncoderInfo
 *
 * @psalm-suppress UnusedClass
 *
 * @implements XmlEncoder<mixed, string>
 */
final readonly class MatchingValueEncoder implements XmlEncoder
{
    /**
     * @param MatchingEncoderDetector $encoderDetector
     * @param XmlEncoder<mixed, string> $defaultEncoder
     */
    public function __construct(
        private Closure $encoderDetector,
        private XmlEncoder $defaultEncoder,
    ) {
    }

    public function iso(Context $context): Iso
    {
        /** @var Iso<string, mixed> $defaultIso */
        $defaultIso = $this->defaultEncoder->iso($context);

        return new Iso(
            to: fn (mixed $value): string => $this->to($context, $value),
            /**
             * @param string|Element $value
             */
            from: static fn (string|Element $value): mixed => $defaultIso->from($value),
        );
    }

    private function to(Context $context, mixed $value): string
    {
        $matchedEncoderInfo = ($this->encoderDetector)($context, $value);
        [$context, $encoder] = match(true) {
            $matchedEncoderInfo instanceof Context => [$matchedEncoderInfo, $this->defaultEncoder],
            default => [$matchedEncoderInfo[0], $matchedEncoderInfo[1] ?? $this->defaultEncoder],
        };

        /** @psalm-suppress RedundantConditionGivenDocblockType - This gives better feedback to people using this encoder */
        // Ensure that the encoderDetector returns valid data.
        invariant($context instanceof Context, 'The MatchingValueEncoder::$encoderDetector callable must return a Context or an array with a Context as first element.');
        invariant($encoder instanceof XmlEncoder, 'The MatchingValueEncoder::$encoderDetector callable must return a Context or an array with a Context as first element and an optional XmlEncoder as second element.');

        return $encoder->iso($context)->to($value);
    }
}
