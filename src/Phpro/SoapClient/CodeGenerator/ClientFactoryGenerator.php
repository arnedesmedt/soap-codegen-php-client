<?php

namespace Phpro\SoapClient\CodeGenerator;

use _HumbugBox8713d481528d\Monolog\Logger;
use Phpro\SoapClient\CodeGenerator\Context\ClientFactoryContext;
use Phpro\SoapClient\Event\Subscriber\LogSubscriber;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapEngineFactory;
use Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\MethodGenerator;

/**
 * Class ClientBuilderGenerator
 *
 * @package Phpro\SoapClient\CodeGenerator
 */
class ClientFactoryGenerator implements GeneratorInterface
{
    const BODY = <<<BODY
\$engine = ExtSoapEngineFactory::fromOptions(
    ExtSoapOptions::defaults(\$wsdl, [])
        ->withClassMap(%2\$s::getCollection())
);
\$eventDispatcher = new EventDispatcher();

if(\$logger) {
    \$eventDispatcher->addSubscriber(new LogSubscriber(\$logger));
}

return new %1\$s(\$engine, \$eventDispatcher);

BODY;


    /**
     * @param FileGenerator $file
     * @param ClientFactoryContext $context
     * @return string
     */
    public function generate(FileGenerator $file, $context): string
    {
        $class = new ClassGenerator($context->getClientName().'Factory');
        $class->setNamespaceName($context->getClientNamespace());
        $class->addUse($context->getClientFqcn());
        $class->addUse($context->getClassmapFqcn());
        $class->addUse(EventDispatcher::class);
        $class->addUse(ExtSoapEngineFactory::class);
        $class->addUse(ExtSoapOptions::class);
        $class->addUse(LogSubscriber::class);
        $class->addUse(LoggerInterface::class);
        $class->addMethodFromGenerator(
            MethodGenerator::fromArray(
                [
                    'name' => 'factory',
                    'static' => true,
                    'body' => sprintf(self::BODY, $context->getClientName(), $context->getClassmapName()),
                    'returntype' => $context->getClientFqcn(),
                    'parameters' => [
                        [
                            'name' => 'wsdl',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'logger',
                            'type' => LoggerInterface::class,
                            'defaultvalue' => null,
                        ],
                    ],
                ]
            )
        );

        $file->setClass($class);

        return $file->generate();
    }
}
