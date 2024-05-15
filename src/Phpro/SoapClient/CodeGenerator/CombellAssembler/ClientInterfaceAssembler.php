<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use Phpro\SoapClient\CodeGenerator\Assembler\AssemblerInterface;
use Phpro\SoapClient\CodeGenerator\Context\ClientContext;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\Exception\AssemblerException;

final class ClientInterfaceAssembler implements AssemblerInterface
{
    /** @param class-string $interface */
    public function __construct(private readonly string $interface)
    {
    }

    public function canAssemble(ContextInterface $context): bool
    {
        return $context instanceof ClientContext;
    }

    /**
     * @param ClientContext $context
     */
    public function assemble(ContextInterface $context): bool
    {
        if (! $context instanceof ClientContext) {
            throw new AssemblerException(
                __METHOD__ . ' expects an ' . ClientContext::class . ' as input ' . $context::class . ' given'
            );
        }

        $class = $context->getClass();
        $class->setImplementedInterfaces([$this->interface]);

        return true;
    }
}
