<?php

declare(strict_types=1);

namespace Phpro\SoapClient\Type;

use EventEngine\JsonSchema\JsonSchemaAwareRecord;

trait FactoryFromArray
{
    /**
     * @return class-string<JsonSchemaAwareRecord>
     */
    abstract protected static function modelClass(): string;

    /**
     * @param array<string, mixed> $array
     *
     * @return mixed
     */
    public static function fromArray(array $array)
    {
        return (self::modelClass())::fromArray($array);
    }
}
