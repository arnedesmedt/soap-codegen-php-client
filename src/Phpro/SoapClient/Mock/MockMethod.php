<?php

namespace Phpro\SoapClient\Mock;

use EventEngine\Data\ImmutableRecord;
use EventEngine\Data\ImmutableRecordLogic;
use RuntimeException;

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
        $parameterType = $this->parameterType();
        /** @var class-string<ImmutableRecord> $responseType */
        $responseType = sprintf('%sResponse', $parameterType);

        $return = $responseType::fromRecordData(
            [
                sprintf('%sResult', explode('\\', $parameterType)[0]) => $return,
            ]
        );

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

    /** @return class-string<ImmutableRecord> */
    private function parameterType(): string
    {
        if (empty($this->parameters)) {
            throw new RuntimeException('No parameters found');
        }

        return $this->parameters[0]::class;
    }

    /** @return array<ImmutableRecord> */
    public function returnValues(): array
    {
        return $this->returnValues;
    }
}