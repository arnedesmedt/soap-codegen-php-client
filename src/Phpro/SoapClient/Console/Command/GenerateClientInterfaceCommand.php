<?php

namespace Phpro\SoapClient\Console\Command;

use Phpro\SoapClient\CodeGenerator\ClientGenerator;
use Phpro\SoapClient\CodeGenerator\ClientInterfaceGenerator;
use Phpro\SoapClient\CodeGenerator\GeneratorInterface;
use Phpro\SoapClient\CodeGenerator\Model\Client;
use Phpro\SoapClient\CodeGenerator\Model\ClientMethodMap;
use Phpro\SoapClient\CodeGenerator\TypeGenerator;
use Phpro\SoapClient\Console\Helper\ConfigHelper;
use Phpro\SoapClient\Util\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Laminas\Code\Generator\FileGenerator;
use function Psl\Type\instance_of;
use function Psl\Type\non_empty_string;

/**
 * Class GenerateClientInterfaceCommand
 *
 * @package Phpro\SoapClient\Console\Command
 */
class GenerateClientInterfaceCommand extends Command
{

    const COMMAND_NAME = 'generate:clientinterface';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
    }

    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Generates a client based on WSDL.')
            ->addOption(
                'config',
                null,
                InputOption::VALUE_REQUIRED,
                'The location of the soap code-generator config file'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $config = $this->getConfigHelper()->load($input);

        $destination = $config->getClientDestination().'/'.$config->getClientName().'.php';
        $methodMap = ClientMethodMap::fromMetadata(
            $config->getTypeNamespace(),
            $config->getEngine()->getMetadata()->getMethods()
        );

        $client = new Client(
            non_empty_string()->assert($config->getClientName()),
            non_empty_string()->coerce($config->getClientNamespace()),
            $methodMap
        );
        $generator = new ClientInterfaceGenerator($config->getRuleSet());
        $fileGenerator = new FileGenerator();
        $this->generateClient(
            $fileGenerator,
            $generator,
            $client,
            $destination
        );

        $io->success('Generated client at ' . $destination);
        
        return 0;
    }

    /**
     * Generates one type class
     *
     * @param FileGenerator $file
     * @param GeneratorInterface $generator
     * @param Client $client
     * @param string $path
     */
    protected function generateClient(FileGenerator $file, GeneratorInterface $generator, Client $client, string $path)
    {
        $code = $generator->generate($file, $client);
        $this->filesystem->putFileContents($path, $code);
    }

    /**
     * Function for added type hint
     */
    public function getConfigHelper(): ConfigHelper
    {
        return instance_of(ConfigHelper::class)->assert($this->getHelper('config'));
    }
}
