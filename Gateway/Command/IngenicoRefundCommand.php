<?php

namespace Netresearch\Epayments\Gateway\Command;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Payment\Gateway\CommandInterface;
use Netresearch\Epayments\Model\Ingenico\Action\Refund\CreateRefund;

class IngenicoRefundCommand implements CommandInterface
{
    /**
     * @var CreateRefund
     */
    private $createRefund;

    /**
     * @var ApiErrorHandler
     */
    private $apiErrorHandler;

    /**
     * IngenicoRefundCommand constructor.
     *
     * @param CreateRefund $createRefund
     * @param ApiErrorHandler $apiErrorHandler
     */
    public function __construct(CreateRefund $createRefund, ApiErrorHandler $apiErrorHandler)
    {
        $this->createRefund = $createRefund;
        $this->apiErrorHandler = $apiErrorHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $commandSubject)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $creditmemo = $payment->getCreditmemo();
        try {
            $this->createRefund->process(
                $payment->getOrder(),
                $creditmemo->getBaseGrandTotal()
            );
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }
}
