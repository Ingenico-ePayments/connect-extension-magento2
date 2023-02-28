<?php

declare(strict_types=1);

namespace Worldline\Connect\Logger;

use Psr\Log\LoggerInterface;
use Worldline\Connect\Api\FrontendLoggerInterface;

use function json_decode;
use function sprintf;

class FrontendLogger implements FrontendLoggerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
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
