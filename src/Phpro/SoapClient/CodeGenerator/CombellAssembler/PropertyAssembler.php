<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use ADS\Util\StringUtil;
use Exception;
use Laminas\Code\Generator\PropertyGenerator;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\Context\PropertyContext;
use Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Phpro\SoapClient\CodeGenerator\Util\Normalizer;
use Phpro\SoapClient\Exception\AssemblerException;

final class PropertyAssembler extends \Phpro\SoapClient\CodeGenerator\Assembler\PropertyAssembler
{
    private string $visibility;

    public function __construct(string $visibility = PropertyGenerator::VISIBILITY_PRIVATE)
    {
        $this->visibility = $visibility;
    }

    /**
     * @param PropertyContext $context
     */
    public function assemble(ContextInterface $context): void
    {
        $class = $context->getClass();
        $property = $context->getProperty();
        try {
            // It's not possible to overwrite a property in laminas-code yet!
            if ($class->hasProperty($property->getName())) {
                return;
            }

            $propertyGenerator = [
                'name' => $property->getName(),
                'visibility' => $this->visibility,
                'omitdefaultvalue' => true,
            ];

            $type = $property->getType();
            $nullableType = Normalizer::removeNullable($type);
            $description = $nullableType . ($property->isNullable() ? '|null' : '');

            if (strpos($class->getName(), 'ArrayOf') === 0
                && $property->getName() === 'item'
            ) {
                $description = sprintf('array<%s>', $description);
                $propertyGenerator['defaultvalue'] = [];
                $propertyGenerator['omitdefaultvalue'] = false;
            }

            $propertyGenerator['docblock'] = DocBlockGeneratorFactory::fromArray(
                [
                    'tags' => [
                        [
                            'name' => 'var',
                            'description' => $description,
                        ],
                    ],
                ]
            );

            $class->addPropertyFromGenerator(PropertyGenerator::fromArray($propertyGenerator));
            // phpcs:ignore SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly.ReferencedGeneralException
        } catch (Exception $e) {
            throw AssemblerException::fromException($e);
        }
    }
}
