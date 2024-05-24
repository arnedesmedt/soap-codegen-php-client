<?php

namespace Phpro\SoapClient\CodeGenerator\Model;

use Phpro\SoapClient\CodeGenerator\Util\Normalizer;
use Soap\Engine\Metadata\Model\Property as MetadataProperty;
use Soap\Engine\Metadata\Model\Type as MetadataType;
use Soap\Engine\Metadata\Model\TypeMeta;
use SplFileInfo;
use function Psl\Type\non_empty_string;

/**
 * Class Type
 *
 * @package Phpro\SoapClient\CodeGenerator\Model
 */
class Type
{
    /**
     * @var non-empty-string
     */
    private $namespace;

    /**
     * @var non-empty-string
     */
    private $xsdName;

    /**
     * @var non-empty-string
     */
    private $name;

    /**
     * @var array
     */
    private $properties = [];

    private TypeMeta $meta;

    /**
     * TypeModel constructor.
     *
     * @param non-empty-string     $namespace
     * @param non-empty-string     $xsdName
     * @param Property[] $properties
     */
    public function __construct(string $namespace, string $xsdName, array $properties, TypeMeta $meta)
    {
        $this->namespace = Normalizer::normalizeNamespace($namespace);
        $this->xsdName = $xsdName;
        $this->name = Normalizer::normalizeClassname($xsdName);
        $this->properties = $properties;
        $this->meta = $meta;
    }

    /**
     * @param non-empty-string $namespace
     */
    public static function fromMetadata(string $namespace, MetadataType $type): self
    {
        return new self(
            $namespace,
            non_empty_string()->assert($type->getName()),
            array_map(
                function (MetadataProperty $property) use ($namespace) {
                    return Property::fromMetaData(
                        $namespace,
                        $property
                    );
                },
                iterator_to_array($type->getProperties())
            ),
            $type->getXsdType()->getMeta(),
        );
    }

    /**
     * @return non-empty-string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return non-empty-string
     */
    public function getXsdName(): string
    {
        return $this->xsdName;
    }

    /**
     * @param non-empty-string $destination
     *
     * @return SplFileInfo
     */
    public function getFileInfo(string $destination, string $suffix = ''): SplFileInfo
    {
        $name = Normalizer::normalizeClassname($this->getName());
        $path = rtrim($destination, '/\\').'/'.$name.$suffix.'.php';

        return new SplFileInfo($path);
    }

    /**
     * @return non-empty-string
     */
    public function getFullName(): string
    {
        $fqnName = sprintf('%s\\%s', $this->getNamespace(), $this->getName());

        return Normalizer::normalizeNamespace($fqnName);
    }

    /**
     * @return Property[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getMeta(): TypeMeta
    {
        return $this->meta;
    }
}
