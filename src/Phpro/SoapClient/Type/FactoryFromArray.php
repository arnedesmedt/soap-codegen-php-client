<?php

namespace Phpro\SoapClient\Type;

use EventEngine\JsonSchema\JsonSchemaAwareRecord;

trait FactoryFromArray
{
    use JsonSchemaAwareRecordLogic;
    
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
