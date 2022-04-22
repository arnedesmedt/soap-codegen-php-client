<?php

declare(strict_types=1);

namespace Phpro\SoapClient\Soap\ExtSoap\Metadata;

use SimpleXMLElement;
use Soap\Engine\Metadata\Collection\MethodCollection;
use Soap\Engine\Metadata\Collection\PropertyCollection;
use Soap\Engine\Metadata\Collection\TypeCollection;
use Soap\Engine\Metadata\Collection\XsdTypeCollection;
use Soap\Engine\Metadata\Metadata;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\ExtSoapEngine\AbusedClient;
use Soap\ExtSoapEngine\Metadata\MethodsParser;
use Soap\ExtSoapEngine\Metadata\XsdTypesParser;

use function assert;

final class SoapMetadata implements Metadata
{
    private AbusedClient $abusedClient;
    /** @var array<string, array<array<string, string>>> */
    private array $types;
    private ?XsdTypeCollection $xsdTypes = null;

    public function __construct(AbusedClient $abusedClient, string $wsdl)
    {
        $this->abusedClient = $abusedClient;

        $xml = simplexml_load_file($wsdl);
        assert($xml instanceof SimpleXMLElement);
        $namespaces = $xml->getNamespaces(true);
        $this->types = [];

        $types = $xml->types->children($namespaces['xsd'])->schema->children($namespaces['xsd']);

        foreach ($types as $elementType => $type) {
            if ($elementType === 'import') {
                continue;
            }

            $attributes = $type->attributes();
            assert($attributes instanceof SimpleXMLElement);
            $typeName = (string) $attributes['name'];

            $newProperties = [];
            $xsdChildren = $type->children($namespaces['xsd']);
            $complexType = $xsdChildren->complexType;
            assert($complexType instanceof SimpleXMLElement);
            $sequence = $complexType->sequence
                ?? $xsdChildren->sequence
                ?? $xsdChildren->all
                ?? null;
            $properties = $sequence ? $sequence->children($namespaces['xsd']) : [];
            foreach ($properties as $property) {
                $attributesOfOneProperty = [];
                $attributes = $property->attributes();
                assert($attributes instanceof SimpleXMLElement);
                foreach ($attributes as $attributeName => $attribute) {
                    $attributesOfOneProperty[$attributeName] = (string) $attribute;
                }

                $newProperties[$attributesOfOneProperty['name']] = $attributesOfOneProperty;
            }

            $this->types[$typeName] = $newProperties;
        }
    }

    public function getTypes(): TypeCollection
    {
        return new TypeCollection(
            ...array_map(
                static function (string $name, array $properties) {
                    return new Type(
                        XsdType::create($name),
                        new PropertyCollection(
                            ...array_map(
                                static function (array $property) {
                                    $type = substr($property['type'], 4);
                                    $nullable = $property['nillable'] ?? false
                                            ? '?'
                                            : '';

                                    return new Property(
                                        $property['name'],
                                        XsdType::create($nullable . $type)
                                    );
                                },
                                $properties
                            )
                        )
                    );
                },
                array_keys($this->types),
                $this->types
            )
        );
    }

    public function getMethods(): MethodCollection
    {
        return (new MethodsParser($this->getXsdTypes()))->parse($this->abusedClient);
    }

    private function getXsdTypes(): XsdTypeCollection
    {
        if ($this->xsdTypes === null) {
            $this->xsdTypes = XsdTypesParser::default()->parse($this->abusedClient);
        }

        return $this->xsdTypes;
    }
}
