<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use ADS\ClientMock\SoapClientMock;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Phpro\SoapClient\Caller\Caller;
use Phpro\SoapClient\CodeGenerator\Assembler\ClientConstructorAssembler;
use Phpro\SoapClient\CodeGenerator\Context\ClientContext;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Phpro\SoapClient\Exception\AssemblerException;
use Phpro\SoapClient\Mock\Mock;
use Phpro\SoapClient\Mock\MockLogic;
use Phpro\SoapClient\Mock\MockPersister;
use Throwable;
use function Psl\Type\non_empty_string;

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
            $persister = $this->generateClassNameAndAddImport(MockPersister::class, $class);
            $class->addPropertyFromGenerator(
                PropertyGenerator::fromArray([
                    'name' => 'persister',
                    'visibility' => PropertyGenerator::VISIBILITY_PRIVATE,
                    'omitdefaultvalue' => true,
                    'docblock' => DocBlockGeneratorFactory::fromArray([
                        'tags' => [
                            [
                                'name'        => 'var',
                                'description' => $persister,
                            ],
                        ]
                    ])
                ])
            );
            $class->addMethodFromGenerator(
                MethodGenerator::fromArray(
                    [
                        'name' => '__construct',
                        'parameters' => [
                            ParameterGenerator::fromArray(
                                [
                                    'name' => 'persister',
                                    'type' => MockPersister::class,
                                ]
                            )
                        ],
                        'visibility' => MethodGenerator::VISIBILITY_PUBLIC,
                        'body' => '$this->persister = $persister->setClient($this);',
                    ]
                )
            );

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

    /**
     * @param non-empty-string $fqcn
     */
    private function generateClassNameAndAddImport(string $fqcn, ClassGenerator $class): string
    {
        $fqcn = non_empty_string()->assert(ltrim($fqcn, '\\'));
        $parts = explode('\\', $fqcn);
        $className = array_pop($parts);

        if (!\in_array($fqcn, $class->getUses(), true)) {
            $class->addUse($fqcn);
        }

        return $className;
    }
}
