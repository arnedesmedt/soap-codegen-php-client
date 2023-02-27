<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use ADS\Util\StringUtil;
use Exception;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Phpro\SoapClient\CodeGenerator\Assembler\AssemblerInterface;
use Phpro\SoapClient\CodeGenerator\Context\ClientContext;
use Phpro\SoapClient\CodeGenerator\Context\ClientMethodContext;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\GeneratorInterface;
use Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Phpro\SoapClient\CodeGenerator\Model\Client;
use Phpro\SoapClient\CodeGenerator\Model\ClientMethod;
use Phpro\SoapClient\CodeGenerator\Model\Parameter;
use Phpro\SoapClient\Exception\AssemblerException;
use Phpro\SoapClient\Type\MultiArgumentRequest;

use function assert;
use function in_array;

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
