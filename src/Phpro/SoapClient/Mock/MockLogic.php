<?php

namespace Phpro\SoapClient\Mock;

use EventEngine\Data\ImmutableRecord;

trait MockLogic
{
    public function __construct(private MockPersister $persister)
    {
    }

    /** @return array<MockMethod> */
    public function calls(): array
    {
        return $this->persister->calls();
    }

    public function withReturnValue(ImmutableRecord $immutableRecord): self
    {
        $this->persister->withReturnValue($immutableRecord);

        return $this;
    }
}