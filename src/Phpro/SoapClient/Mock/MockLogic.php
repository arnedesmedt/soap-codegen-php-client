<?php

namespace Phpro\SoapClient\Mock;

/**
 * @property MockPersister $caller
 */
trait MockLogic
{
    /** @return array<MockMethod> */
    public function calls(): array
    {
        return $this->caller->calls();
    }
}