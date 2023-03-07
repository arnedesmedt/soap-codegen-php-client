<?php

namespace Phpro\SoapClient\Mock;

interface Mock
{
    /** @return array<MockMethod> */
    public function calls(): array;

    public function mockInterface(): string;
}