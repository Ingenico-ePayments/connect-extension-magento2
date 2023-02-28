<?php

declare(strict_types=1);

namespace Worldline\Connect\Api;

// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface FrontendLoggerInterface
{
    /**
     * Log API request data from the frontend
     *
     * @param string type The type of request
     * @param string $jsonData The data that is sent in the request
     * @param string|null $requestId (Optional) an ID that can be used to match a request with a response
     * @return bool
     * @api
     */
    public function logRequest(string $type, string $jsonData, ?string $requestId): bool;

    /**
     * Log API response data from the frontend
     *
     * @param string $jsonData The data is received in the frontend.
     * @param string|null $requestId (Optional) an ID that can be used to match a response with a request
     * @return bool
     */
    public function logResponse(string $jsonData, ?string $requestId): bool;
}
