<?php
declare(strict_types=1);

namespace Soap\Encoding;

use Soap\Encoding\Encoder\Context;
use Soap\Engine\Encoder as SoapEncoder;
use Soap\Engine\HttpBinding\SoapRequest;
use Soap\Engine\Metadata\Metadata;
use function VeeWee\Reflecta\Lens\index;

final class Encoder implements SoapEncoder
{
    public function __construct(
        private readonly Metadata $metadata,
        private readonly EncoderRegistry $registry
    ) {
    }

    public function encode(string $method, array $arguments): SoapRequest
    {
        // TODO  : invariants
        // | inputBindingUsage  | literal                                                                                                |
        // | bindingStyle       | document                                                                                               |

        $methodInfo = $this->metadata->getMethods()->fetchByName($method);
        $meta = $methodInfo->getMeta();

        $request = [];
        foreach ($methodInfo->getParameters() as $index => $parameter)
        {
            $type = $parameter->getType();
            $context = new Context($type, $this->metadata, $this->registry);
            $argument = index($index)->get($arguments);
            $request[] = $this->registry->findByXsdType($type)->iso($context)->from($argument);
        }

        // TODO Wrap envelope

        return new SoapRequest(
            implode('', $request),
            $meta->location()->unwrap(),
            $meta->action()->unwrap(),
            $meta->soapVersion()->unwrap(),
            $meta->isOneWay()->unwrapOr(false)
        );
    }
}
