<?php

declare(strict_types=1);

namespace Phpro\SoapClient\Soap\ExtSoap\Metadata\Manipulators;

use Phpro\SoapClient\Soap\Metadata\Manipulators\TypesManipulatorInterface;
use SimpleXMLElement;
use Soap\Engine\Metadata\Collection\PropertyCollection;
use Soap\Engine\Metadata\Collection\TypeCollection;
use Soap\Engine\Metadata\Model\Property;
use Soap\Engine\Metadata\Model\Type;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\ExtSoapEngine\ExtSoapOptions;

use function assert;

class TypesManipulator implements TypesManipulatorInterface
{
    /** @var array<string, array<string, array<string, string>>> */
    private array $types;

    public function __construct(ExtSoapOptions $options)
    {
        $this->types = $this->types($options->getWsdl());
    }

    public function __invoke(TypeCollection $types): TypeCollection
    {
        return new TypeCollection(
            ...array_map(
                static function (string $name, array $properties): Type {
                    $properties = array_map(
                        static function (array $property): Property {
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
                    );

                    return new Type(
                        XsdType::create($name),
                        new PropertyCollection(...$properties)
                    );
                },
                array_keys($this->types),
                $this->types
            )
        );
    }

    /**
     * @return array<string, array<string, array<string, string>>>
     */
    private function types(string $wsdl): array
    {
        $xml = simplexml_load_file($wsdl);
        assert($xml instanceof SimpleXMLElement);
        $namespaces = $xml->getNamespaces(true);
        $result = [];

        $types = $xml
            ->types
            ->children($namespaces['xsd'])
            ->schema
            ->children($namespaces['xsd']);

        foreach ($types as $elementType => $type) {
            if ($elementType === 'import') {
                continue;
            }

            $attributes = $type->attributes();
            assert($attributes instanceof SimpleXMLElement);
            $typeName = (string) $attributes['name'];

            $newProperties = [];
            $children = $type->children($namespaces['xsd']);
            assert($children instanceof SimpleXMLElement);
            $complexType = $children->complexType;
            assert($complexType instanceof SimpleXMLElement);
            $sequence = $complexType->sequence
                ?? $children->sequence
                ?? $children->all
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

            $result[$typeName] = $newProperties;
        }

        return $result;
    }
}
