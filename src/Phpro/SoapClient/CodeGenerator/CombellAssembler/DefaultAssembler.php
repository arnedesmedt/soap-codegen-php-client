<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use Exception;
use Laminas\Code\Generator\MethodGenerator;
use Phpro\SoapClient\CodeGenerator\Assembler\AssemblerInterface;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Phpro\SoapClient\Exception\AssemblerException;
use RuntimeException;

final class DefaultAssembler implements AssemblerInterface
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
            $originalName = $class->getName();
            $typeNamespace = str_replace('Default', 'Type', $class->getNamespaceName());
            $class->setName($originalName . 'Default');
            $class->addUse($typeNamespace . '\\' . $originalName);
            $class->addUse(RuntimeException::class);

            $class->addMethodFromGenerator(
                MethodGenerator::fromArray(
                    [
                        'static' => true,
                        'visibility' => MethodGenerator::VISIBILITY_PUBLIC,
                        'name' => 'default',
                        'returnType' => $typeNamespace . '\\' . $originalName,
                        'body' => sprintf(
                            'throw new RunTimeException(\'Default object for \\\'%s\\\' not implemented yet.\');',
                            $originalName
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
