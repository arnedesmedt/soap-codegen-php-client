<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use ADS\Util\StringUtil;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Phpro\SoapClient\CodeGenerator\Assembler\ClientMethodAssembler;
use Phpro\SoapClient\CodeGenerator\Context\ClientMethodContext;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\GeneratorInterface;
use Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Phpro\SoapClient\CodeGenerator\Model\ClientMethod;
use Phpro\SoapClient\CodeGenerator\Model\Parameter;
use Phpro\SoapClient\CodeGenerator\Util\Normalizer;
use Phpro\SoapClient\CodeGenerator\Util\TypeChecker;
use Phpro\SoapClient\Exception\AssemblerException;
use Phpro\SoapClient\Exception\SoapException;
use Phpro\SoapClient\Mock\MockPersister;
use Phpro\SoapClient\Type\MultiArgumentRequest;

use Phpro\SoapClient\Type\RequestInterface;
use Phpro\SoapClient\Type\ResultInterface;
use function assert;
use function in_array;
use function Psl\Type\non_empty_string;

final class ClientMockMethodAssembler extends ClientMethodAssembler
{
    /**
     * @param ClientMethodContext $context
     */
    public function assemble(ContextInterface $context): bool
    {
        if (!$context instanceof ClientMethodContext) {
            throw new AssemblerException(
                __METHOD__.' expects an '.ClientMethodContext::class.' as input '.get_class($context).' given'
            );
        }

        $class = $context->getClass();
        $method = $context->getMethod();

        try {
            $phpMethodName = StringUtil::camelize($method->getMethodName());
            $param = $this->createParamsFromContext($context);
            $class->removeMethod($phpMethodName);
            $docblock = $context->getArgumentCount() > 1 ?
                $this->generateMultiArgumentDocblock($context) :
                $this->generateSingleArgumentDocblock($context);
            $methodBody = $this->generateMethodBody($class, $param, $method);

            $class->addMethodFromGenerator(
                MethodGenerator::fromArray(
                    [
                        'name' => $phpMethodName,
                        'parameters' => $param === null ? [] : [$param],
                        'visibility' => MethodGenerator::VISIBILITY_PUBLIC,
                        'body' => $methodBody,
                        'returntype' => 'self',
                        'docblock' => $docblock,
                    ]
                )
            );
        } catch (\Exception $e) {
            throw AssemblerException::fromException($e);
        }

        return true;
    }

    /**
     * @param ParameterGenerator|null $param
     * @param ClientMethod $method
     *
     * @return string
     */
    private function generateMethodBody(ClassGenerator $class, ?ParameterGenerator $param, ClientMethod $method): string
    {
        return sprintf(
            '($this->persister)(\'%s\', %s); return $this;',
            $method->getMethodName(),
            $param === null
                ? 'new '.$this->generateClassNameAndAddImport(MultiArgumentRequest::class, $class).'([])'
                : '$'.$param->getName()
        );
    }

    /**
     * @param ClientMethodContext $context
     *
     * @return ParameterGenerator|null
     */
    private function createParamsFromContext(ClientMethodContext $context): ?ParameterGenerator
    {
        if ($context->getArgumentCount() === 0) {
            return null;
        }

        if ($context->getArgumentCount() === 1) {
            $param = current($context->getMethod()->getParameters());

            return ParameterGenerator::fromArray($param->toArray());
        }

        return ParameterGenerator::fromArray(
            [
                'name' => 'multiArgumentRequest',
                'type' => MultiArgumentRequest::class,
            ]
        );
    }

    /**
     * @param ClientMethodContext $context
     *
     * @return DocBlockGenerator
     */
    private function generateMultiArgumentDocblock(ClientMethodContext $context): DocBlockGenerator
    {
        $class = $context->getClass();
        $method = $context->getMethod();
        $description = ['MultiArgumentRequest with following params:'. GeneratorInterface::EOL];
        foreach ($context->getMethod()->getParameters() as $parameter) {
            $description[] = $parameter->getType().' $'.$parameter->getName();
        }

        return DocBlockGeneratorFactory::fromArray(
            [
                'longdescription' => implode(GeneratorInterface::EOL, $description),
                'tags' => [
                    ['name' => 'param', 'description' => MultiArgumentRequest::class],
                ],
            ]
        );
    }

    /**
     * @param ClientMethodContext $context
     *
     * @return DocBlockGenerator
     */
    private function generateSingleArgumentDocblock(ClientMethodContext $context): DocBlockGenerator
    {
        $method = $context->getMethod();
        $class = $context->getClass();
        $param = current($method->getParameters());
        $data = ['tags' => []];

        if ($param) {
            array_unshift(
                $data['tags'],
                [
                    'name' => 'param',
                    'description' => sprintf(
                        '%s $%s',
                        $this->generateClassNameAndAddImport($param->getType(), $class, true),
                        $param->getName()
                    ),
                ]
            );
        }

        return DocBlockGeneratorFactory::fromArray($data)
            ->setWordWrap(false);
    }

    /**
     * @param non-empty-string $fqcn Fully qualified class name.
     * @param ClassGenerator $class Class generator object.
     * @param bool $prefixed
     *
     * @return string
     */
    protected function generateClassNameAndAddImport(string $fqcn, ClassGenerator $class, $prefixed = false): string
    {
        if (TypeChecker::isKnownType($fqcn)) {
            return $fqcn;
        }
        $prefix = '';
        $fqcn = ltrim($fqcn, '\\');
        $parts = explode('\\', $fqcn);
        $className = array_pop($parts);
        if ($prefixed) {
            $prefix = array_pop($parts);
        }
        $classNamespace = implode('\\', $parts);
        $currentNamespace = (string)$class->getNamespaceName();
        if ($prefixed) {
            $className = $prefix.'\\'.$className;
            $fqcn = $classNamespace.'\\'.$prefix;
        }
        if ($classNamespace !== $currentNamespace || !\in_array($fqcn, $class->getUses(), true)) {
            $class->addUse(non_empty_string()->assert($fqcn));
        }

        return $className;
    }
}
