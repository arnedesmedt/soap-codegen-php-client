<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use ADS\Util\StringUtil;
use Exception;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Phpro\SoapClient\CodeGenerator\Assembler\AssemblerInterface;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\Context\PropertyContext;
use Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Phpro\SoapClient\CodeGenerator\Util\Normalizer;
use Phpro\SoapClient\Exception\AssemblerException;
use Phpro\SoapClient\Type\FactoryFromArray;

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

        try {
            $class->setName($class->getName() . 'Factory');
            $class->addTrait(FactoryFromArray::class);
            $class->addMethodFromGenerator(
                MethodGenerator::fromArray(
                    [
                        'static' => true,
                        'visibility' => MethodGenerator::VISIBILITY_PROTECTED,
                        'name' => 'modelClass',
                        'returnType' => 'string',
                        'body' => sprintf(
                            'return %s\%s::class',
                            str_replace($class->getNamespaceName(), 'Factory', 'Type'),
                            $class->getName()
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
