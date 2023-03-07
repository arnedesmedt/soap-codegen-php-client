<?php

namespace Phpro\SoapClient\Mock;

use EventEngine\Data\ImmutableRecord;
use EventEngine\Data\ImmutableRecordLogic;

class MockMethod implements ImmutableRecord
{
    use ImmutableRecordLogic;

    private string $method;
    private array $parameters = [];
    private array $returnValues = [];

    /** @return array<string, class-string> */
    private static function arrayPropItemTypeMap(): array
    {
        return [
            'parameters' => ImmutableRecord::class,
            'returnValues' => ImmutableRecord::class,
        ];
    }

    public function merge(MockMethod $lastCall): self
    {
        return $this->with([
            'parameters' => [...$this->parameters, ...$lastCall->parameters()],
            'returnValues' => [...$this->returnValues, ...$lastCall->returnValues()],
        ]);
    }

    public function addReturnValue(ImmutableRecord $return): self
    {
        return $this->with(['returnValues' => [...$this->returnValues, $return]]);
    }

    public function method(): string
    {
        return $this->method;
    }

    /** @return array<ImmutableRecord> */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /** @return array<ImmutableRecord> */
    public function returnValues(): array
    {
        return $this->returnValues;
    }
}