<?php

namespace Netresearch\Epayments\Gateway\Command;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Payment\Gateway\CommandInterface;
use Netresearch\Epayments\Model\Ingenico\Action\CancelPayment;

class IngenicoCancelCommand implements CommandInterface
{
    /**
     * @var CancelPayment
     */
    private $cancelPayment;

    /**
     * @var ApiErrorHandler
     */
    private $apiErrorHandler;

    /**
     * IngenicoCancelCommand constructor.
     *
     * @param CancelPayment $cancelPayment
     * @param ApiErrorHandler $apiErrorHandler
     */
    public function __construct(CancelPayment $cancelPayment, ApiErrorHandler $apiErrorHandler)
    {
        $this->cancelPayment = $cancelPayment;
        $this->apiErrorHandler = $apiErrorHandler;
    }

    /**
     * @param mixed [] $commandSubject
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(array $commandSubject)
    {
        try {
            $this->cancelPayment->process($commandSubject['payment']->getPayment()->getOrder());
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }
}
