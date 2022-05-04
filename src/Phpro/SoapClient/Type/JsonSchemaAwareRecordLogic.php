<?php

declare(strict_types=1);

namespace Phpro\SoapClient\Type;

trait JsonSchemaAwareRecordLogic
{
    use \ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic {
        toArray as parentToArray;
        with as parentWith;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        // phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        if (self::$__propTypeMap === null) {
            self::$__propTypeMap = self::buildPropTypeMap();
        }

        // phpcs:enable Squiz.NamingConventions.ValidVariableName.NotCamelCaps

        return $this->parentToArray();
    }

    /**
     * @param array<mixed> $recordData
     *
     * @return static
     */
    public function with(array $recordData): self
    {
        // phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        if (self::$__propTypeMap === null) {
            self::$__propTypeMap = self::buildPropTypeMap();
        }

        // phpcs:enable Squiz.NamingConventions.ValidVariableName.NotCamelCaps

        return $this->parentWith($recordData);
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }
}
