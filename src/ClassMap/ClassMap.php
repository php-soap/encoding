<?php
declare(strict_types=1);

namespace Soap\Encoding\ClassMap;

final class ClassMap
{
    /**
     * @param non-empty-string $xmlNamespace
     * @param non-empty-string $wsdlType
     * @param class-string $phpClassName
     */
    public function __construct(
        private readonly string $xmlNamespace,
        private readonly string $wsdlType,
        private readonly string $phpClassName
    ) {
    }

    /**
     * @return class-string
     */
    public function getPhpClassName(): string
    {
        return $this->phpClassName;
    }

    /**
     * @return non-empty-string
     */
    public function getXmlType(): string
    {
        return $this->wsdlType;
    }

    /**
     * @return non-empty-string
     */
    public function getXmlNamespace(): string
    {
        return $this->xmlNamespace;
    }
}
