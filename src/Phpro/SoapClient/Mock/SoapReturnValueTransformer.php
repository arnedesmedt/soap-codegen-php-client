<?php

namespace Phpro\SoapClient\Mock;

use ADS\ClientMock\MockMethod;
use ADS\ClientMock\ReturnValueTransformer;
use EventEngine\Data\ImmutableRecord;
use RuntimeException;

class SoapReturnValueTransformer implements ReturnValueTransformer
{
    /**
     * @param ImmutableRecord|array<ImmutableRecord>|bool $returnValue
     * @return ImmutableRecord|array<ImmutableRecord>|bool
     */
    public function __invoke(
        ImmutableRecord|array|bool $returnValue,
        MockMethod|null $method = null,
    ): ImmutableRecord|array|bool {
        if ($method === null) {
            throw new RuntimeException('No method found.');
        }

        $parametersPerCall = $method->parametersPerCall();

        if (empty($parametersPerCall)) {
            throw new RuntimeException(sprintf('No parameters found for method %s.', $method->method()));
        }

        /** @var array<ImmutableRecord> $parametersForFirstCall */
        $parametersForFirstCall = $parametersPerCall[0];

        if (empty($parametersForFirstCall)) {
            throw new RuntimeException(
                sprintf('No parameters found for first call of method %s.', $method->method()),
            );
        }

        $parameterType = $parametersForFirstCall[0]::class;
        $parameterParts = explode('\\', $parameterType);
        $parameterName = array_pop($parameterParts);

        /** @var class-string<ImmutableRecord> $responseType */
        $responseType = sprintf('%sResponse', $parameterType);

        if (is_array($returnValue)) {
            $returnValue = array_map(
                static fn (ImmutableRecord $record) => $record->toArray(),
                $returnValue,
            );

            $returnValue = ['item' => $returnValue];
        }

        if ($returnValue instanceof ImmutableRecord) {
            $returnValue = $returnValue->toArray();
        }

        return $responseType::fromArray(
            [
                sprintf('%sResult', $parameterName) => $returnValue,
            ]
        );
    }
}