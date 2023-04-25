<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use Exception;
use Laminas\Code\Generator\MethodGenerator;
use Phpro\SoapClient\CodeGenerator\Assembler\AssemblerInterface;
use Phpro\SoapClient\CodeGenerator\Assembler\TraitAssembler;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Phpro\SoapClient\Exception\AssemblerException;
use ADS\JsonImmutableObjects\FactoryFromArray;

final class FactoryAssembler implements AssemblerInterface
{
    public function canAssemble(ContextInterface $context): bool
    {
        return $context instanceof TypeContext;
    }

    /**
     * @param TypeContext $context
     */
    public function assemble(ContextInterface $context): void
    {
        $class = $context->getClass();

        $traitAssembler = (new TraitAssembler(FactoryFromArray::class));

        if ($traitAssembler->canAssemble($context)) {
            $traitAssembler->assemble($context);
        }

        try {
            $originalName = $class->getName();
            $typeNamespace = str_replace('Factory', 'Type', $class->getNamespaceName());
            $class->setName($originalName . 'Factory');
            $class->addUse($typeNamespace . '\\' . $originalName);
            
            $class->setDocBlock(
                DocBlockGeneratorFactory::fromArray(
                    [
                        'tags' => [
                            [
                                'name' => 'method',
                                'description' => sprintf(
                                    'static %s fromArray(array $array)',
                                    $originalName
                                )
                            ]
                        ],
                    ]
                )
            );

            $class->addMethodFromGenerator(
                MethodGenerator::fromArray(
                    [
                        'static' => true,
                        'visibility' => MethodGenerator::VISIBILITY_PROTECTED,
                        'name' => 'modelClass',
                        'returnType' => 'string',
                        'body' => sprintf(
                            'return %s::class;',
                            $originalName
                        ),
                        'docblock' => DocBlockGeneratorFactory::fromArray(
                            [
                                'tags' => [
                                    [
                                        'name' => 'return',
                                        'description' => 'class-string<'. $originalName .'>',
                                    ],
                                ],
                            ]
                        ),
                    ]
                )
            );
            // phpcs:ignore SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly.ReferencedGeneralException
        } catch (Exception $e) {
            throw AssemblerException::fromException($e);
        }
    }
}
