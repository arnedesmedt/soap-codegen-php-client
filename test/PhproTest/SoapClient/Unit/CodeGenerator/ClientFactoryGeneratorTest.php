<?php

namespace PhproTest\SoapClient\Unit\CodeGenerator;

use Laminas\Code\Generator\ClassGenerator;
use Phpro\SoapClient\CodeGenerator\ClientFactoryGenerator;
use Phpro\SoapClient\CodeGenerator\Context\ClassMapContext;
use Phpro\SoapClient\CodeGenerator\Context\ClientContext;
use Phpro\SoapClient\CodeGenerator\Context\ClientFactoryContext;
use PHPUnit\Framework\TestCase;
use Laminas\Code\Generator\FileGenerator;

class ClientFactoryGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $expected = <<<BODY
<?php

namespace App\Client;

use App\Client\Myclient;
use App\Classmap\SomeClassmap;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Phpro\SoapClient\Soap\CombellDefaultEngineFactory;
use Soap\ExtSoapEngine\ExtSoapOptions;
use Phpro\SoapClient\Caller\EventDispatchingCaller;
use Phpro\SoapClient\Caller\EngineCaller;
use Phpro\SoapClient\Event\Subscriber\LogSubscriber;
use Psr\Log\LoggerInterface;
use Soap\ExtSoapEngine\Wsdl\PermanentWsdlLoaderProvider;
use Soap\Wsdl\Loader\FlatteningLoader;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use Soap\ExtSoapEngine\Wsdl\Naming\Md5Strategy;
use Soap\Psr18Transport\Psr18Transport;
use Http\Client\Common\PluginClient;
use Symfony\Component\HttpClient\Psr18Client;
use Phpro\SoapClient\Soap\ClientErrorPlugin;
use Http\Client\Common\Plugin;

class MyclientFactory
{
    /**
     * @param array<Plugin> \$plugins
     */
    public static function factory(string \$wsdl, \Symfony\Component\EventDispatcher\EventDispatcher \$eventDispatcher = null, \Psr\Log\LoggerInterface \$logger = null, array \$plugins = []) : \App\Client\Myclient
    {
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
                ->withClassMap(SomeClassmap::getCollection())
                ->withWsdlProvider(\$provider),
            \$transport,
        );

        \$eventDispatcher ??= new EventDispatcher();
        \$caller = new EventDispatchingCaller(new EngineCaller(\$engine), \$eventDispatcher);

        if(\$logger) {
            \$eventDispatcher->addSubscriber(new LogSubscriber(\$logger));
        }

        return new Myclient(\$caller);
    }

    public static function createMock() : \App\Client\MyclientMock
    {
        return new MyclientMock(new \ADS\ClientMock\MockPersister(new ReturnValueTransformer()));
    }
}


BODY;
        $clientContext = new ClientContext(new ClassGenerator(), 'Myclient', 'App\\Client');
        $classMapContext = new ClassMapContext(
            new FileGenerator(),
            new \Phpro\SoapClient\CodeGenerator\Model\TypeMap('App\\Types', []),
            'SomeClassmap',
            'App\\Classmap'
        );
        $context = new ClientFactoryContext($clientContext, $classMapContext);
        $generator = new ClientFactoryGenerator();
        self::assertEquals($expected, $generator->generate(new FileGenerator(), $context));
    }
}
