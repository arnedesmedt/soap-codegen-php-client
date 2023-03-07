<?php

namespace Phpro\SoapClient\Mock;

use EventEngine\Data\ImmutableRecord;
use Phpro\SoapClient\Type\RequestInterface;
use RuntimeException;

class MockPersister
{
    private MockMethod|null $lastCall = null;
    /** @var array<MockMethod> */
    private array $calls = [];

    public function __invoke(string $method, RequestInterface $request): self
    {
        assert($request instanceof ImmutableRecord);

        if ($this->lastCall !== null) {
            throw new RuntimeException('You must call withReturnValue() after calling a method.');
        }

        $this->lastCall = MockMethod::fromArray(
            [
                'method' => $method,
                'parameters' => [$request],
            ],
        );

        return $this;
    }

    public function withReturnValue(ImmutableRecord $return): void
    {
        assert($this->lastCall instanceof MockMethod);

        $lastCall = $this->lastCall->addReturnValue($return);

        if (isset($this->calls[$lastCall->method()])) {
            $lastCall = $this->calls[$lastCall->method()]->merge($lastCall);
        }

        $this->calls[$lastCall->method()] = $lastCall;

        $this->lastCall = null;
    }

    /** @return array<MockMethod> */
    public function calls(): array
    {
        return $this->calls;
    }
}