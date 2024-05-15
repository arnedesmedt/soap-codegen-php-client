<?php

namespace Phpro\SoapClient\Mock;

use ADS\ClientMock\MockMethod;
use ADS\ClientMock\ReturnValueTransformer;
use EventEngine\Data\ImmutableRecord;
use RuntimeException;

abstract class SoapReturnValueTransformer implements ReturnValueTransformer
{
    /**
     * @param ImmutableRecord|array<ImmutableRecord>|bool|string $returnValue
     * @return ImmutableRecord|array<ImmutableRecord>|bool|string
     */
    public function __invoke(
        ImmutableRecord|array|bool|string $returnValue,
        MockMethod|null $method = null,
    ): ImmutableRecord|array|bool|string {
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
        $resultType = sprintf('%sResult', $parameterName);

        if (is_array($returnValue)) {
            if (empty($returnValue)) {
                return $responseType::fromArray([$resultType => ['item' => $returnValue]]);
            }

            /** @var class-string<ImmutableRecord> $arrayClassName */
            $arrayClassName = $this->arrayClassName($returnValue);
            $returnValue = $arrayClassName::fromRecordData(['item' => $returnValue]);
        }

        return $responseType::fromRecordData([$resultType => $returnValue]);
    }

    /**
     * @param array<int, ImmutableRecord> $returnValue
     * @return class-string<ImmutableRecord>
     */
    abstract public function arrayClassName(array $returnValue): string;
}
