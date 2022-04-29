<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use ADS\ClientMock\SoapClientMock;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Phpro\SoapClient\CodeGenerator\Assembler\ClientConstructorAssembler;
use Phpro\SoapClient\CodeGenerator\Context\ClientContext;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Phpro\SoapClient\Exception\AssemblerException;
use Throwable;

final class ClientMockConstructorAssembler extends ClientConstructorAssembler
{
    public function assemble(ContextInterface $context): bool
    {
        if (! $context instanceof ClientContext) {
            throw new AssemblerException(
                __METHOD__ . ' expects an ' . ClientContext::class . ' as input ' . $context::class . ' given'
            );
        }

        $class = $context->getClass();
        $clientNamespace = str_replace('Mock', 'Client', $class->getNamespaceName() ?? '');
        $clientName = str_replace('Mock', '', $class->getName());
        $clientFactoryName = str_replace('Mock', 'Factory', $class->getName());
        $class->addUse(MockInterface::class);
        $class->addUse(LegacyMockInterface::class);
        $class->addUse(SoapClientMock::class);
        $class->addUse(sprintf('%s\%s', $clientNamespace, $clientName));
        $class->addUse(sprintf('%s\%s', $clientNamespace, $clientFactoryName));

        try {
            $class->setExtendedClass(SoapClientMock::class);
            $class->addPropertyFromGenerator(
                PropertyGenerator::fromArray(
                    [
                        'name' => 'client',
                        'visibility' => PropertyGenerator::VISIBILITY_PROTECTED,
                        'defaultvalue' => null,
                        'static' => true,
                        'docblock' => DocBlockGeneratorFactory::fromArray(
                            [
                                'tags' => [
                                    [
                                        'name' => 'var',
                                        'description' => 'MockInterface|LegacyMockInterface|null',
                                    ],
                                ],
                            ]
                        ),
                    ]
                )
            );
            $class->addPropertyFromGenerator(
                PropertyGenerator::fromArray(
                    [
                        'name' => 'mocks',
                        'visibility' => PropertyGenerator::VISIBILITY_PRIVATE,
                        'defaultvalue' => [],
                        'static' => true,
                        'docblock' => DocBlockGeneratorFactory::fromArray(
                            [
                                'tags' => [
                                    [
                                        'name' => 'var',
                                        'description' => 'array<string, array<array<string, array<mixed>>>>',
                                    ],
                                ],
                            ]
                        ),
                    ]
                )
            );
            $class->addMethodFromGenerator(
                MethodGenerator::fromArray(
                    [
                        'name' => 'factoryClass',
                        'visibility' => MethodGenerator::VISIBILITY_PROTECTED,
                        'static' => true,
                        'body' => sprintf('return %s::class;', $clientFactoryName),
                        'returntype' => 'string',
                    ]
                )
            );
            $class->addMethodFromGenerator(
                MethodGenerator::fromArray(
                    [
                        'name' => 'clientClass',
                        'visibility' => MethodGenerator::VISIBILITY_PROTECTED,
                        'static' => true,
                        'body' => sprintf('return %s::class;', $clientName),
                        'returntype' => 'string',
                    ]
                )
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return true;
    }
}
