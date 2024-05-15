<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use ADS\Util\StringUtil;
use Laminas\Code\Generator\ClassGenerator;
use Phpro\SoapClient\CodeGenerator\Assembler\ClientMethodAssembler;
use Phpro\SoapClient\CodeGenerator\Context\ClientMethodContext;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Phpro\SoapClient\Exception\AssemblerException;

use function in_array;

final class ClientMockeryMethodAssembler extends ClientMethodAssembler
{
    /**
     * @param ClientMethodContext $context
     */
    public function assemble(ContextInterface $context): bool
    {
        if (! $context instanceof ClientMethodContext) {
            throw new AssemblerException(
                __METHOD__ . ' expects an ' . ClientMethodContext::class . ' as input ' . $context::class . ' given'
            );
        }

        $class = $context->getClass();
        $docBlockGenerator = $class->getDocBlock();
        $tag = [
            'name' => 'method',
            'description' => sprintf(
                'static void %s($response, ...$request)',
                StringUtil::camelize($context->getMethod()->getMethodName())
            ),
        ];

        if ($docBlockGenerator === null) {
            $class->setDocBlock(DocBlockGeneratorFactory::fromArray(['tags' => [$tag]]));

            return true;
        }

        $docBlockGenerator->setTag($tag);

        return true;
    }

    /**
     * @param string         $fqcn  Fully qualified class name.
     * @param ClassGenerator $class Class generator object.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    protected function generateClassNameAndAddImport(
        string $fqcn,
        ClassGenerator $class,
        $prefixed = false
    ): string {
        $prefix = '';
        $fqcn = ltrim($fqcn, '\\');

        if (preg_match('/VoidType$/', $fqcn)) {
            return 'void';
        }

        $parts = explode('\\', $fqcn);
        $className = array_pop($parts);
        if ($prefixed) {
            $prefix = array_pop($parts);
        }

        $classNamespace = implode('\\', $parts);
        $currentNamespace = (string) $class->getNamespaceName();
        if ($prefixed) {
            $className = $prefix . '\\' . $className;
            $fqcn = $classNamespace . '\\' . $prefix;
        }

        if ($classNamespace !== $currentNamespace || ! in_array($fqcn, $class->getUses(), true)) {
            $class->addUse($fqcn);
        }

        return $className;
    }
}
