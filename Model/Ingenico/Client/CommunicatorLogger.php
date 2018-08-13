<?php

namespace Netresearch\Epayments\Model\Ingenico\Client;

use Psr\Log\LoggerInterface;

class CommunicatorLogger implements \Ingenico\Connect\Sdk\CommunicatorLogger
{
    const DATE_FORMAT_STRING = DATE_ATOM;

    /** @var LoggerInterface */
    private $logger;

    /**
     * CommunicatorLogger constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function log($message)
    {
        $this->logger->info($message);
    }

    /**
     * {@inheritdoc}
     */
    public function logException($message, \Exception $exception)
    {
        $this->logger->error($message, ['exception' => $exception]);
    }
}
