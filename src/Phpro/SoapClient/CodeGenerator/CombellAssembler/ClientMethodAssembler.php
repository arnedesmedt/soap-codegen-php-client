<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use ADS\Util\StringUtil;
use Exception;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Phpro\SoapClient\CodeGenerator\Context\ClientMethodContext;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\GeneratorInterface;
use Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Phpro\SoapClient\CodeGenerator\Model\Client;
use Phpro\SoapClient\CodeGenerator\Model\ClientMethod;
use Phpro\SoapClient\CodeGenerator\Model\Parameter;
use Phpro\SoapClient\Exception\AssemblerException;
use Phpro\SoapClient\Type\MultiArgumentRequest;

use SebastianBergmann\Type\VoidType;
use function assert;
use function in_array;

final class ClientMethodAssembler extends \Phpro\SoapClient\CodeGenerator\Assembler\ClientMethodAssembler
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
        $class->setExtendedClass(Client::class);
        $method = $context->getMethod();

        try {
            $phpMethodName = StringUtil::camelize($method->getMethodName());
            $param = $this->createParamsFromContext($context);
            $class->removeMethod($phpMethodName);
            $isVoid = (bool) preg_match('/VoidType$/', $method->getReturnType()->getType());

            $docblock = $method->getParametersCount() > 1 ?
                $this->generateMultiArgumentDocblock($context) :
                $this->generateSingleArgumentDocblock($context);
            $methodBody = $this->generateMethodBody($class, $param, $method, $isVoid);

            $class->addMethodFromGenerator(
                MethodGenerator::fromArray(
                    [
                        'name' => $phpMethodName,
                        'parameters' => $param === null ? [] : [$param],
                        'visibility' => MethodGenerator::VISIBILITY_PUBLIC,
                        'body' => $methodBody,
                        'returntype' => $isVoid
                            ? 'void'
                            : $method->getReturnType()->getType(),
                        'docblock' => $docblock,
                    ]
                )
            );
            // phpcs:ignore SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly.ReferencedGeneralException
        } catch (Exception $e) {
            throw AssemblerException::fromException($e);
        }

        return true;
    }

    private function generateMethodBody(
        ClassGenerator $class,
        ?ParameterGenerator $param,
        ClientMethod $method,
        bool $isVoid
    ): string {
        $parameter = $param === null
            ? 'new ' . $this->generateClassNameAndAddImport(MultiArgumentRequest::class, $class) . '([])'
            : '$' . $param->getName();

        return $isVoid
            ? sprintf('($this->caller)(\'%s\', %s);', $method->getMethodName(), $parameter)
            : sprintf(
                '$result = ($this->caller)(\'%s\', %s); assert($result instanceof %s); return $result;',
                $method->getMethodName(),
                $parameter,
                $method->getReturnType()->getType()
            );
    }

    private function createParamsFromContext(ClientMethodContext $context): ?ParameterGenerator
    {
        $method = $context->getMethod();
        if ($method->getParametersCount() === 0) {
            return null;
        }

        if ($method->getParametersCount() === 1) {
            $param = current($method->getParameters());
            assert($param instanceof Parameter);

            return ParameterGenerator::fromArray($param->toArray());
        }

        return ParameterGenerator::fromArray(
            [
                'name' => 'multiArgumentRequest',
                'type' => MultiArgumentRequest::class,
            ]
        );
    }

    private function generateMultiArgumentDocblock(ClientMethodContext $context): DocBlockGenerator
    {
        $class = $context->getClass();
        $method = $context->getMethod();
        $description = ['MultiArgumentRequest with following params:' . GeneratorInterface::EOL];
        foreach ($method->getParameters() as $parameter) {
            $description[] = $parameter->getType() . ' $' . $parameter->getName();
        }

        return DocBlockGeneratorFactory::fromArray(
            [
                'longdescription' => implode(GeneratorInterface::EOL, $description),
                'tags' => [
                    ['name' => 'param', 'description' => MultiArgumentRequest::class],
                    [
                        'name' => 'return',
                        'description' => $this->generateClassNameAndAddImport(
                            $method->getReturnType()->getType(),
                            $class,
                            true
                        ),
                    ],
                ],
            ]
        );
    }

    private function generateSingleArgumentDocblock(ClientMethodContext $context): DocBlockGenerator
    {
        $method = $context->getMethod();
        $class = $context->getClass();
        $param = current($method->getParameters());

        $data = [
            'tags' => [
                [
                    'name' => 'return',
                    'description' => $this->generateClassNameAndAddImport(
                        $method->getReturnType()->getType(),
                        $class,
                        true
                    ),
                ],
            ],
        ];

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
