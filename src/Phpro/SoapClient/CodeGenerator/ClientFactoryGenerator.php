<?php

namespace Phpro\SoapClient\CodeGenerator;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\Psr18Client;
use Phpro\SoapClient\Caller\EngineCaller;
use Phpro\SoapClient\Caller\EventDispatchingCaller;
use Phpro\SoapClient\CodeGenerator\Context\ClientFactoryContext;
use Phpro\SoapClient\Soap\ClientErrorPlugin;
use Phpro\SoapClient\Soap\CombellDefaultEngineFactory;
use Soap\ExtSoapEngine\ExtSoapOptions;
use Phpro\SoapClient\Event\Subscriber\LogSubscriber;
use Psr\Log\LoggerInterface;
use Soap\ExtSoapEngine\Wsdl\Naming\Md5Strategy;
use Soap\ExtSoapEngine\Wsdl\PermanentWsdlLoaderProvider;
use Soap\Psr18Transport\Psr18Transport;
use Soap\Wsdl\Loader\FlatteningLoader;
use Soap\Wsdl\Loader\StreamWrapperLoader;
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
\$provider = new PermanentWsdlLoaderProvider(
    new FlatteningLoader(new StreamWrapperLoader()),
    new Md5Strategy(),
);

\$transport = Psr18Transport::createForClient(
    new PluginClient(
        new Psr18Client(),
        [
            new ClientErrorPlugin(),
            ...\$plugins,
        ],
    ),
);

\$engine = CombellDefaultEngineFactory::create(
    ExtSoapOptions::defaults(\$wsdl, [])
        ->withClassMap(%2\$s::getCollection())
        ->withWsdlProvider(\$provider),
    \$transport,
);

\$eventDispatcher ??= new EventDispatcher();
\$caller = new EventDispatchingCaller(new EngineCaller(\$engine), \$eventDispatcher);

if(\$logger) {
    \$eventDispatcher->addSubscriber(new LogSubscriber(\$logger));
}

return new %1\$s(\$caller);

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
        $class->addUse(CombellDefaultEngineFactory::class);
        $class->addUse(ExtSoapOptions::class);
        $class->addUse(EventDispatchingCaller::class);
        $class->addUse(EngineCaller::class);
        $class->addUse(LogSubscriber::class);
        $class->addUse(LoggerInterface::class);
        $class->addUse(PermanentWsdlLoaderProvider::class);
        $class->addUse(FlatteningLoader::class);
        $class->addUse(StreamWrapperLoader::class);
        $class->addUse(Md5Strategy::class);
        $class->addUse(Psr18Transport::class);
        $class->addUse(PluginClient::class);
        $class->addUse(Psr18Client::class);
        $class->addUse(ClientErrorPlugin::class);
        $class->addUse(Plugin::class);
        $class->addMethodFromGenerator(
            MethodGenerator::fromArray(
                [
                    'docblock' => ['tags' => [['name' => 'param', 'description' => 'array<Plugin> $plugins']]],
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
                            'name' => 'eventDispatcher',
                            'type' => EventDispatcher::class,
                            'defaultvalue' => null,
                        ],
                        [
                            'name' => 'logger',
                            'type' => LoggerInterface::class,
                            'defaultvalue' => null,
                        ],
                        [
                            'name' => 'plugins',
                            'type' => 'array',
                            'defaultvalue' => [],
                        ],
                    ],
                ]
            )
        );

        $class->addMethodFromGenerator(
            MethodGenerator::fromArray(
                [
                    'name' => 'createMock',
                    'static' => true,
                    'body' => sprintf(
                        'return new %sMock(new \ADS\ClientMock\MockPersister(new ReturnValueTransformer()));',
                        $context->getClientName()
                    ),
                    'returnType' => sprintf(
                        '%sMock',
                        $context->getClientNamespace() . '\\' . $context->getClientName()
                    ),
                ],
            ),
        );

        $file->setClass($class);

        return $file->generate();
    }
}
