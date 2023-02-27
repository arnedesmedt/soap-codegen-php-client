<?php

namespace Phpro\SoapClient\CodeGenerator;

use Laminas\Code\Generator\Exception\ClassNotFoundException;
use Laminas\Code\Generator\InterfaceGenerator;
use Phpro\SoapClient\CodeGenerator\Context\ClientContext;
use Phpro\SoapClient\CodeGenerator\Context\ClientMethodContext;
use Phpro\SoapClient\CodeGenerator\Context\FileContext;
use Phpro\SoapClient\CodeGenerator\Context\PropertyContext;
use Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Phpro\SoapClient\CodeGenerator\Model\Client;
use Phpro\SoapClient\CodeGenerator\Model\Type;
use Phpro\SoapClient\CodeGenerator\Rules\RuleSetInterface;
use Laminas\Code\Generator\FileGenerator;

/**
 * Class ClientGenerator
 *
 * @package Phpro\SoapClient\CodeGenerator
 */
class ClientInterfaceGenerator implements GeneratorInterface
{
    /**
     * @var RuleSetInterface
     */
    private $ruleSet;

    /**
     * TypeGenerator constructor.
     *
     * @param RuleSetInterface $ruleSet
     */
    public function __construct(RuleSetInterface $ruleSet)
    {
        $this->ruleSet = $ruleSet;
    }

    /**
     * @param FileGenerator $file
     * @param Client        $client
     *
     * @return string
     */
    public function generate(FileGenerator $file, $client): string
    {
        try {
            // @phpstan-ignore-next-line
            $interface = $file->getClass() ?: new InterfaceGenerator();
        } catch (ClassNotFoundException $exception) {
            $interface = new InterfaceGenerator();
        }
        $interface->setNamespaceName($client->getNamespace());
        $interface->setName($client->getName());

        $this->ruleSet->applyRules(new ClientContext($interface, $client->getName(), $client->getNamespace()));

        $methods = $client->getMethodMap();
        foreach ($methods->getMethods() as $method) {
            $this->ruleSet->applyRules(new ClientMethodContext($interface, $method));
        }

        $this->ruleSet->applyRules(new FileContext($file));
        $file->setClass($interface);

        return $file->generate();
    }
}
