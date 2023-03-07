<?php

namespace Phpro\SoapClient\Mock;

use EventEngine\Data\ImmutableRecord;
use EventEngine\Data\ImmutableRecordLogic;
use RuntimeException;

class MockMethod
{
    private array $parameters = [];
    private array $returnValues = [];

    public function __construct(
        private string $method,
    ) {
    }

    public function merge(MockMethod $lastCall): self
    {
        if ($lastCall->method() !== $this->method) {
            throw new RuntimeException('Cannot merge calls with different methods');
        }

        $this->parameters = [...$this->parameters, ...$lastCall->parameters()];
        $this->returnValues = [...$this->returnValues, ...$lastCall->returnValues()];

        return $this;
    }

    public function addParameter(ImmutableRecord $parameter): self
    {
        $this->parameters[] = $parameter;
        return $this;
    }

    /** @param ImmutableRecord|array<ImmutableRecord> $returnValue */
    public function addReturnValue(ImmutableRecord|array $returnValue): self
    {
        $parameterType = $this->parameterType();
        /** @var class-string<ImmutableRecord> $responseType */
        $responseType = sprintf('%sResponse', $parameterType);

        if (is_array($returnValue)) {
            $returnValue = ['item' => $responseType::fromArray($returnValue)];
        }

        $returnValue = $responseType::fromRecordData(
            [
                sprintf('%sResult', explode('\\', $parameterType)[0]) => $returnValue,
            ]
        );

        $this->returnValues[] = $returnValue;

        return $this;
    }

    /** @return class-string<ImmutableRecord> */
    private function parameterType(): string
    {
        if (empty($this->parameters)) {
            throw new RuntimeException('No parameters found');
        }

        return $this->parameters[0]::class;
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