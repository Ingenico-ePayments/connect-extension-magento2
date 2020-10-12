<?php

declare(strict_types=1);

namespace Ingenico\Connect\Logger;

use Ingenico\Connect\Api\FrontendLoggerInterface;
use Psr\Log\LoggerInterface;

class FrontendLogger implements FrontendLoggerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function logRequest(string $type, string $jsonData, ?string $requestId): bool
    {
        $this->logger->debug(
            sprintf('Logging Frontend Request : %1$s', $type),
            $this->makeContext($jsonData, $requestId)
        );

        return true;
    }

    public function logResponse(string $jsonData, ?string $requestId): bool
    {
        $this->logger->debug(
            'Logging Frontend Response',
            $this->makeContext($jsonData, $requestId)
        );

        return true;
    }

    private function makeContext(string $jsonData, ?string $requestId): array
    {
        $context = ['json' => json_decode($jsonData, true)];
        if ($requestId !== null) {
            $context['requestId'] = $requestId;
        }
        return $context;
    }
}
