<?php

namespace Phpro\SoapClient\CodeGenerator;

use Laminas\Code\Generator\TraitGenerator;
use Phpro\SoapClient\CodeGenerator\Context\FileContext;
use Laminas\Code\Generator\Exception\ClassNotFoundException;
use Phpro\SoapClient\CodeGenerator\Context\PropertyContext;
use Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Phpro\SoapClient\CodeGenerator\Model\Type;
use Phpro\SoapClient\CodeGenerator\Rules\RuleSetInterface;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\FileGenerator;

/**
 * Class TypeGenerator
 *
 * @package Phpro\SoapClient\CodeGenerator
 */
class TypeTraitGenerator implements GeneratorInterface
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
     * @param Type          $type
     *
     * @return string
     */
    public function generate(FileGenerator $file, $type): string
    {
        try {
            // @phpstan-ignore-next-line
            $class = $file->getClass() ?: new TraitGenerator();
        } catch (ClassNotFoundException $exception) {
            $class = new TraitGenerator();
        }
        $class->setNamespaceName($type->getNamespace());
        $class->setName($type->getName());

        $this->ruleSet->applyRules(new TypeContext($class, $type));
        $this->ruleSet->applyRules(new FileContext($file));

        foreach ($type->getProperties() as $property) {
            $this->ruleSet->applyRules(new PropertyContext($class, $type, $property));
        }

        $file->setClass($class);

        return $file->generate();
    }
}
