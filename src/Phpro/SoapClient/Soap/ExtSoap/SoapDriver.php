<?php

declare(strict_types=1);

namespace Phpro\SoapClient\Soap\ExtSoap;

use EventEngine\Data\ImmutableRecord;
use Phpro\SoapClient\Soap\ExtSoap\Metadata\SoapMetadata;
use ReflectionClass;
use Soap\Engine\Decoder;
use Soap\Engine\Driver;
use Soap\Engine\Encoder;
use Soap\Engine\HttpBinding\SoapRequest;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Engine\Metadata\LazyInMemoryMetadata;
use Soap\Engine\Metadata\Metadata;
use Soap\ExtSoapEngine\AbusedClient;
use Soap\ExtSoapEngine\ExtSoapDecoder;
use Soap\ExtSoapEngine\ExtSoapEncoder;
use Soap\ExtSoapEngine\ExtSoapMetadata;
use Soap\ExtSoapEngine\ExtSoapOptions;
use Soap\ExtSoapEngine\Generator\DummyMethodArgumentsGenerator;

use function assert;
use function is_object;

final class SoapDriver implements Driver
{
    private AbusedClient $client;
    private Encoder $encoder;
    private Decoder $decoder;
    private Metadata $metadata;

    public function __construct(
        AbusedClient $client,
        Encoder $encoder,
        Decoder $decoder,
        Metadata $metadata
    ) {
        $this->client = $client;
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->metadata = $metadata;
    }

    public static function createFromOptions(ExtSoapOptions $options): self
    {
        $client = AbusedClient::createFromOptions($options);

        return self::createFromClient(
            $client,
            new LazyInMemoryMetadata(new SoapMetadata($client, $options->getWsdl()))
        );
    }

    public static function createFromClient(AbusedClient $client, ?Metadata $metadata = null): self
    {
        $metadata ??= new LazyInMemoryMetadata(new ExtSoapMetadata($client));

        return new self(
            $client,
            new ExtSoapEncoder($client),
            new ExtSoapDecoder($client, new DummyMethodArgumentsGenerator($metadata)),
            $metadata
        );
    }

    public function decode(string $method, SoapResponse $response)
    {
        $decoded = $this->decoder->decode($method, $response);

        if (! is_object($decoded)) {
            return $decoded;
        }

        $reflectionClass = new ReflectionClass($decoded);

        if (! $reflectionClass->implementsInterface(ImmutableRecord::class)) {
            return $decoded;
        }

        /** @var class-string<ImmutableRecord> $class */
        $class = $reflectionClass->getName();

        return $class::fromRecordData((array) $decoded);
    }

    /**
     * @param array<mixed> $arguments
     */
    public function encode(string $method, array $arguments): SoapRequest
    {
        return $this->encoder->encode($method, $arguments);
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getClient(): AbusedClient
    {
        return $this->client;
    }
}
