<?php
declare(strict_types=1);

namespace Soap\Encoding\Test\Unit;

use Soap\Encoding\Encoder\Context;
use Soap\Encoding\EncoderRegistry;
use Soap\Engine\Metadata\Collection\MethodCollection;
use Soap\Engine\Metadata\Collection\TypeCollection;
use Soap\Engine\Metadata\InMemoryMetadata;
use Soap\Engine\Metadata\Metadata;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\Wsdl\Loader\CallbackLoader;
use Soap\WsdlReader\Metadata\Wsdl1MetadataProvider;
use Soap\WsdlReader\Model\Definitions\BindingStyle;
use Soap\WsdlReader\Model\Definitions\BindingUse;
use Soap\WsdlReader\Model\Definitions\Namespaces;
use Soap\WsdlReader\Wsdl1Reader;

trait ContextCreatorTrait
{
    public static function createContext(
        XsdType $currentType,
        TypeCollection $allTypes = new TypeCollection()
    ): Context {
        return new Context(
            $currentType,
            new InMemoryMetadata(
                $allTypes,
                new MethodCollection(),
            ),
            EncoderRegistry::default(),
            new Namespaces([], []), // TODO : ::empty() constructor.
        );
    }

    public static function createContextFromMetadata(
        Metadata $metadata,
        string $typeName,
        ?string $namespace = null,
    ): Context {
        $type = $namespace
            ? $metadata->getTypes()->fetchByNameAndXmlNamespace($typeName, $namespace)
            : $metadata->getTypes()->fetchFirstByName($typeName);

        return new Context(
            $type->getXsdType(),
            $metadata,
            EncoderRegistry::default(),
            new Namespaces([], []),
        );
    }

    public static function createMetadataFromWsdl(
        string $schema,
        string $type,
        string $namespace = 'https://test',
        BindingStyle $style = BindingStyle::RPC,
        BindingUse $use = BindingUse::ENCODED,
        string $attributeFormDefault=''
    ): Metadata {
        $wsdl = <<<EOXSD
              <definitions name="WsdlTest"
                  xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                  xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
                  xmlns:tns="$namespace"
                  xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
                  xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
                  xmlns="http://schemas.xmlsoap.org/wsdl/"
                  targetNamespace="$namespace"
              >
                <types>
                <schema xmlns="http://www.w3.org/2001/XMLSchema" targetNamespace="$namespace" $attributeFormDefault>
                  $schema
                </schema>
                </types>
                <message name="testMessage">
                  <part name="testParam" $type/>
                </message>
                <portType name="testPortType">
                      <operation name="test">
                          <input message="testMessage"/>
                      </operation>
                </portType>
                <binding name="testBinding" type="testPortType">
                      <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
                      <operation name="test">
                          <soap:operation soapAction="#test" style="$style->value"/>
                          <input>
                              <soap:body use="$use->value" namespace="$namespace" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
                          </input>
                      </operation>
                </binding>
                <service name="testService">
                <port name="testPort" binding="tns:testBinding">
                    <soap:address location="test://" />
                </port>
               </service>
              </definitions>
        EOXSD;

        $wsdl = (new Wsdl1Reader(new CallbackLoader(fn() => $wsdl)))('some.wsdl');
        $metadataProvider = new Wsdl1MetadataProvider($wsdl);

        return $metadataProvider->getMetadata();
    }
}
