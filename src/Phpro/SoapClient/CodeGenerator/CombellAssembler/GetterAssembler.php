<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use ADS\Util\StringUtil;
use Exception;
use Laminas\Code\Generator\MethodGenerator;
use Phpro\SoapClient\CodeGenerator\Assembler\GetterAssemblerOptions;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\Context\PropertyContext;
use Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Phpro\SoapClient\Exception\AssemblerException;

final class GetterAssembler extends \Phpro\SoapClient\CodeGenerator\Assembler\GetterAssembler
{
    private GetterAssemblerOptions $options;

    public function __construct(?GetterAssemblerOptions $options = null)
    {
        $this->options = $options ?? new GetterAssemblerOptions();
    }

    /**
     * @param PropertyContext $context
     */
    public function assemble(ContextInterface $context): void
    {
        $class = $context->getClass();
        $property = $context->getProperty();

        try {
            $methodName = StringUtil::camelize($property->getName());
            $class->removeMethod($methodName);

            $methodGenerator = new MethodGenerator($methodName);
            $methodGenerator->setVisibility(MethodGenerator::VISIBILITY_PUBLIC);
            $methodGenerator->setBody(sprintf('return $this->%s;', $methodName));

            $docBlockReturnType = $property->getType();

            if ($this->options->useReturnType()) {
                $returnType = $property->getCodeReturnType();

                if ($returnType !== null && strpos($returnType, '?') !== 0 && $property->isNullable()) {
                    $returnType = '?' . $returnType;
                }

                $extra = [];

                if (strpos($class->getName(), 'ArrayOf') === 0
                    && $property->getName() === 'item'
                ) {
                    $docBlockReturnType = sprintf('array<%s>', $returnType);
                    $returnType = 'array';
                }

                $methodGenerator->setReturnType($returnType);
            }

            if ($this->options->useDocBlocks()) {
                $methodGenerator->setDocBlock(
                    DocBlockGeneratorFactory::fromArray(
                        [
                            'tags' => [
                                [
                                    'name'        => 'return',
                                    'description' => $docBlockReturnType,
                                ],
                            ],
                        ]
                    )
                );
            }

            $class->addMethodFromGenerator($methodGenerator);
        // phpcs:ignore SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly.ReferencedGeneralException
        } catch (Exception $exception) {
            throw AssemblerException::fromException($exception);
        }
    }
}
