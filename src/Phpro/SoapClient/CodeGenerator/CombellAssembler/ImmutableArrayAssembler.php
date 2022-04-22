<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use Laminas\Code\Generator\MethodGenerator;
use Phpro\SoapClient\CodeGenerator\Assembler\AssemblerInterface;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Phpro\SoapClient\CodeGenerator\Model\Property;

final class ImmutableArrayAssembler implements AssemblerInterface
{
    public function canAssemble(ContextInterface $context) : bool
    {
        return $context instanceof TypeContext;
    }

    /**
     * @param TypeContext $context
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function assemble(ContextInterface $context) : void
    {
        $class = $context->getClass();
        $properties = $context->getType()->getProperties();

        $propertyList = array_map(
            static function (Property $property) {
                $type = strpos($property->getType(), '\\') === 0
                    ? substr($property->getType(), 1)
                    : $property->getType();

                return sprintf(
                    '\'%s\' => \'%s\'',
                    $property->getName(),
                    $type
                );
            },
            $properties
        );
        $body = sprintf('return [%s];', implode(',', $propertyList));

        $class->addMethodFromGenerator(
            MethodGenerator::fromArray(
                [
                    'visibility' => 'private',
                    'static' => true,
                    'name' => 'arrayPropItemTypeMap',
                    'returntype' => 'array',
                    'body' => $body,
                    'docblock' => [
                        'tags' => [
                            [
                                'name' => 'return',
                                'description' => 'array<string, string>',
                            ],
                            [
                                'name' => 'phpcsSuppress',
                                'description' => 'SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod',
                            ],
                        ],
                    ],
                ]
            )
        );
    }
}
