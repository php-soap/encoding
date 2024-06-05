<?php
declare(strict_types=1);

namespace Soap\Encoding\TypeInference;

use Soap\Encoding\Encoder\Context;
use Soap\Engine\Metadata\Collection\PropertyCollection;
use Soap\Engine\Metadata\Model\Type;
use function Psl\Vec\flat_map;

final class ComplexTypeBuilder
{
    public function __invoke(Context $context): Type
    {
        $type = $context->metadata->getTypes()->fetchByNameAndXmlNamespace(
            $context->type->getName(),
            $context->type->getXmlNamespace()
        );
        $extensions = $this->detectExtensions($context, $type);

        return new Type(
            $type->getXsdType(),
            new PropertyCollection(
                ...flat_map(
                    $extensions,
                    static fn(Type $type): iterable => $type->getProperties()
                ),
                ...$type->getProperties(),
            )
        );
    }

    /**
     * @return list<Type>
     */
    private function detectExtensions(Context $context, Type $type): array
    {
        $allTypes = $context->metadata->getTypes();
        $typeMeta = $type->getXsdType()->getMeta();

        $extends = $typeMeta->extends()
            ->filter(static fn (array $extends): bool => !$extends['isSimple'])
            ->map(static fn (array $extends): Type => $allTypes->fetchByNameAndXmlNamespace($extends['type'], $extends['namespace']));

        return $extends
            ->map(fn (Type $extendedType): array => [...$this->detectExtensions($context, $extendedType), $extendedType])
            ->unwrapOr([]);
    }
}
