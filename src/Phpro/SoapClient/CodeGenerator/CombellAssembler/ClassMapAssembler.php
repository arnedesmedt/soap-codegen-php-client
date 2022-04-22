<?php

declare(strict_types=1);

namespace Phpro\SoapClient\CodeGenerator\CombellAssembler;

use Exception;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Phpro\SoapClient\CodeGenerator\Assembler\AssemblerInterface;
use Phpro\SoapClient\CodeGenerator\Context\ClassMapContext;
use Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Phpro\SoapClient\CodeGenerator\Model\TypeMap;
use Phpro\SoapClient\Exception\AssemblerException;
use Soap\ExtSoapEngine\Configuration\ClassMap\ClassMap;
use Soap\ExtSoapEngine\Configuration\ClassMap\ClassMapCollection;
use Soap\ExtSoapEngine\Configuration\ClassMap\ClassMapInterface;

class ClassMapAssembler implements AssemblerInterface
{
    public function canAssemble(ContextInterface $context): bool
    {
        return $context instanceof ClassMapContext;
    }

    /**
     * @param ClassMapContext|ContextInterface $context
     *
     * @throws AssemblerException
     */
    public function assemble(ContextInterface $context): void
    {
        assert($context instanceof ClassMapContext);

        $class = ClassGenerator::fromArray(
            [
                'name' => $context->getName(),
            ]
        );
        $file = $context->getFile();
        $file->setClass($class);
        $file->setNamespace($context->getNamespace());
        $typeMap = $context->getTypeMap();
        $typeNamespace = $typeMap->getNamespace();
        $file->setUse($typeNamespace, preg_match('/\\\\Type$/', $typeNamespace) ? null : 'Type');

        try {
            $file->setUse(ClassMapCollection::class);
            $file->setUse(ClassMap::class);
            $linefeed = $file::LINE_FEED;
            $classMap = $this->assembleClassMap($typeMap, $linefeed, $file->getIndentation());
            $code = $this->assembleClassMapCollection($classMap, $linefeed) . $linefeed;
            $class->addUse(ClassMapCollection::class);
            $class->addUse(ClassMapInterface::class);
            $class->addMethodFromGenerator(
                MethodGenerator::fromArray(
                    [
                        'name'       => 'getCollection',
                        'static'     => true,
                        'body'       => 'return ' . $code,
                        'returntype' => ClassMapCollection::class,
                        'docBlock' => DocBlockGeneratorFactory::fromArray(
                            [
                                'tags' => [
                                    [
                                        'name' => 'return',
                                        'description' => 'ClassMapCollection<string, ClassMapInterface>',
                                    ],
                                ],
                            ]
                        ),
                    ]
                )
            );
            // phpcs:ignore SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly.ReferencedGeneralException
        } catch (Exception $e) {
            throw AssemblerException::fromException($e);
        }
    }

    private function assembleClassMap(TypeMap $typeMap, string $linefeed, string $indentation): string
    {
        $classMap = [];
        foreach ($typeMap->getTypes() as $type) {
            $classMap[] = sprintf(
                '%snew ClassMap(\'%s\', %s::class),',
                $indentation,
                $type->getXsdName(),
                'Type\\' . $type->getName()
            );
        }

        return implode($linefeed, $classMap);
    }

    private function assembleClassMapCollection(string $classMap, string $linefeed): string
    {
        $code = [
            'new ClassMapCollection(',
            '%s',
            ');',
        ];

        return sprintf(implode($linefeed, $code), $classMap);
    }
}
