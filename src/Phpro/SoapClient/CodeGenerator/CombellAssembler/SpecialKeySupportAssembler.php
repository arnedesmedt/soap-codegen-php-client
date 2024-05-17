<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use ADS\Util\StringUtil;
use EventEngine\Data\SpecialKeySupport;
use Exception;
use Laminas\Code\Generator\MethodGenerator;
use Mockery\Generator\Method;
use Phpro\SoapClient\CodeGenerator\Assembler\AssemblerInterface;
use Phpro\SoapClient\CodeGenerator\Assembler\GetterAssemblerOptions;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\Context\PropertyContext;
use Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Phpro\SoapClient\Exception\AssemblerException;
use SebastianBergmann\Type\Type;

final class SpecialKeySupportAssembler implements AssemblerInterface
{

    public function canAssemble(ContextInterface $context): bool
    {
        return $context instanceof TypeContext;
    }

    public function assemble(ContextInterface $context)
    {
        assert($context instanceof TypeContext);

        $class = $context->getClass();
        $class->setImplementedInterfaces([SpecialKeySupport::class]);

        $class->addMethods(
            [
                (new MethodGenerator(
                    name: 'convertKeyForRecord',
                    parameters: [
                        [
                            'name' => 'key',
                            'type' => 'string',
                        ],
                    ],
                    body: '',
                ))
                    ->setReturnType('string'),
                (new MethodGenerator(
                    name: 'convertKeyForArray',
                    parameters: [
                        [
                            'name' => 'key',
                            'type' => 'string',
                        ],
                    ],
                    body: '',
                ))
                    ->setReturnType('string'),
            ],
        );
    }
}
