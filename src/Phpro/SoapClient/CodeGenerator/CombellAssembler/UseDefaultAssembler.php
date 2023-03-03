<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use Exception;
use Laminas\Code\Generator\MethodGenerator;
use Phpro\SoapClient\CodeGenerator\Assembler\AssemblerInterface;
use Phpro\SoapClient\CodeGenerator\Assembler\UseAssembler;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Phpro\SoapClient\Exception\AssemblerException;
use RuntimeException;

final class UseDefaultAssembler implements AssemblerInterface
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
        var_dump($class->getNamespaceName());

        try {
            $defaultNameSpace = str_replace('Type', 'Default', $class->getNamespaceName() ?? '');
            $trait = $defaultNameSpace . '\\' . $class->getName() . 'Default';
            $assembler = new UseAssembler($trait);

            if ($assembler->canAssemble($context)) {
                $assembler->assemble($context);
            }

            $class->addTrait($class->getName() . 'Default');
            // phpcs:ignore SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly.ReferencedGeneralException
        } catch (Exception $e) {
            throw AssemblerException::fromException($e);
        }
    }
}
