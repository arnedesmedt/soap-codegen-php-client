<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use ADS\ClientMock\Mock;
use ADS\ClientMock\MockLogic;
use Laminas\Code\Generator\MethodGenerator;
use Phpro\SoapClient\CodeGenerator\Assembler\ClientConstructorAssembler;
use Phpro\SoapClient\CodeGenerator\Context\ClientContext;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Phpro\SoapClient\Exception\AssemblerException;

final class ClientMockConstructorAssembler extends ClientConstructorAssembler
{
    public function assemble(ContextInterface $context)
    {
        if (!$context instanceof ClientContext) {
            throw new AssemblerException(
                __METHOD__.' expects an '.ClientContext::class.' as input '.get_class($context).' given'
            );
        }

        $class = $context->getClass();
        try {
            $interfaceName = str_replace('Mock', 'Interface', $class->getName());
            $class->addMethodFromGenerator(
                MethodGenerator::fromArray(
                    [
                        'name' => 'mockInterface',
                        'visibility' => MethodGenerator::VISIBILITY_PUBLIC,
                        'body' => sprintf('return %s::class;', $interfaceName),
                        'returnType' => 'string',
                        'docblock' => DocBlockGeneratorFactory::fromArray(
                            [
                                'tags' => [
                                    [
                                        'name' => 'return',
                                        'description' => 'class-string<'. $interfaceName .'>',
                                    ],
                                ],
                            ]
                        ),
                    ]
                ),
            );

            $class->addUse($class->getNamespaceName() . '\\' . $interfaceName);
            $class->addTrait('\\' . MockLogic::class);
            $class->setImplementedInterfaces([Mock::class]);
        } catch (\Exception $e) {
            throw AssemblerException::fromException($e);
        }

        return true;
    }
}
