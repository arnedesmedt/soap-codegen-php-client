<?php

namespace Phpro\SoapClient\Event\Subscriber;

use Phpro\SoapClient\Event\RequestEvent;
use Phpro\SoapClient\Event\ResponseEvent;
use Phpro\SoapClient\Event\FaultEvent;
use Phpro\SoapClient\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LogSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var float
     */
    private $requestTime;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param RequestEvent $event
     */
    public function onClientRequest(RequestEvent $event)
    {
        $this->requestTime = microtime(true);

        $this->logger->info(
            '[phpro/soap-client] request "{method}"',
            [
                'method' => $event->getMethod(),
                'request' => $event->getRequest()
            ]
        );
    }

    /**
     * @param ResponseEvent $event
     */
    public function onClientResponse(ResponseEvent $event)
    {
        $this->logger->info(
            '[phpro/soap-client] response "{method}"',
            [
                'method' => $event->getRequestEvent()->getMethod(),
                'response' => $event->getResponse(),
                'duration' => $this->requestTime
                    ? round(microtime(true) - $this->requestTime, 3)
                    : null
            ]
        );
    }

    /**
     * @param FaultEvent $event
     */
    public function onClientFault(FaultEvent $event)
    {
        $this->logger->info(
            '[phpro/soap-client] fault "{fault}" for "{method}"',
            [
                'fault' => $event->getSoapException()->getMessage(),
                'method' => $event->getRequestEvent()->getMethod(),
                'request' => $event->getRequestEvent()->getRequest(),
                'duration' => $this->requestTime
                    ? round(microtime(true) - $this->requestTime, 3)
                    : null
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return array(
            Events::REQUEST  => 'onClientRequest',
            Events::RESPONSE => 'onClientResponse',
            Events::FAULT => 'onClientFault'
        );
    }
}
