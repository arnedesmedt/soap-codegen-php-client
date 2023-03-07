<?php

namespace Phpro\SoapClient\Mock;

use EventEngine\Data\ImmutableRecord;

interface Mock
{
    /** @return array<MockMethod> */
    public function calls(): array;

    public function mockInterface(): string;

    public function withReturnValue(ImmutableRecord $immutableRecord): self;
}