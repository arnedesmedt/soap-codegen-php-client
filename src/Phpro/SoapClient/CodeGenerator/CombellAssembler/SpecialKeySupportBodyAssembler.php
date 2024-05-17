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

final class SpecialKeySupportBodyAssembler implements AssemblerInterface
{

    public function canAssemble(ContextInterface $context): bool
    {
        return $context instanceof PropertyContext;
    }

    public function assemble(ContextInterface $context): void
    {
        assert($context instanceof PropertyContext);

        $class = $context->getClass();
        $property = $context->getProperty();

        $convertKeyForRecord = $class->getMethod('convertKeyForRecord');

        if ($convertKeyForRecord instanceof MethodGenerator) {
            $convertKeyForRecord
                ->setBody(sprintf("return '%s';", $property->getName()));
        }

        $convertKeyForArray = $class->getMethod('convertKeyForArray');

        if ($convertKeyForRecord instanceof MethodGenerator) {
            $convertKeyForArray
                ->setBody(sprintf("return '%s';", StringUtil::camelize($property->getName())));
        }
    }
}
