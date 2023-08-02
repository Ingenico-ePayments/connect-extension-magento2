<?php

declare(strict_types=1);

namespace Worldline\Connect\Gateway\Command;

use Ingenico\Connect\Sdk\Domain\Errors\Definitions\APIError;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Psr\Log\LoggerInterface;

use function __;
use function array_reduce;
use function sprintf;

class ApiErrorHandler
{
    public function __construct(
        private readonly ManagerInterface $manager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param ResponseException $e
     * @throws CommandException
     */
    public function handleError(ResponseException $e)
    {
        $errors = $e->getErrors();
        $message = array_reduce(
            $errors,
            static function (
                $message,
                APIError $error
            ) {
                $message .= sprintf(
                    "HTTP: %s Message: %s \n",
                    $error->httpStatusCode,
                    $error->message
                );

                return $message;
            },
            ''
        );
        $this->manager->addErrorMessage($message);
        $this->logger->error($message);

        throw new CommandException(__($e->getMessage()));
    }
}
