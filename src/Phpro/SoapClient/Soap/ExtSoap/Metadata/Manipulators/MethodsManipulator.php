<?php

declare(strict_types=1);

namespace Phpro\SoapClient\Soap\ExtSoap\Metadata\Manipulators;

use Phpro\SoapClient\Soap\Metadata\Manipulators\MethodsManipulatorInterface;
use Soap\Engine\Metadata\Collection\MethodCollection;

class MethodsManipulator implements MethodsManipulatorInterface
{
    public function __invoke(MethodCollection $methods): MethodCollection
    {
        return $methods;
    }
}
