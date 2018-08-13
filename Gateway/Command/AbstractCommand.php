<?php

namespace Netresearch\Epayments\Gateway\Command;

use Ingenico\Connect\Sdk\Domain\Errors\Definitions\APIError;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

class AbstractCommand
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * IngenicoRefundCommand constructor.
     *
     * @param ManagerInterface $manager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ManagerInterface $manager,
        LoggerInterface $logger
    ) {
        $this->messageManager = $manager;
        $this->logger = $logger;
    }

    /**
     * @param ResponseException $e
     */
    protected function handleError(ResponseException $e)
    {
        $errors = $e->getErrors();
        $message = array_reduce(
            $errors,
            function (
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
        $this->messageManager->addErrorMessage($message);
        $this->logger->error($message);

        throw $e;
    }
}
