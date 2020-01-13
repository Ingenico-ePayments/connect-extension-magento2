<?php

namespace Ingenico\Connect\Gateway\Command;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\Ingenico\Action\CreateHostedCheckout;

class Initialize implements CommandInterface
{
    /**
     * @var CreateHostedCheckout
     */
    private $hostedCheckout;

    /**
     * @var ApiErrorHandler
     */
    private $apiErrorHandler;

    /**
     * Initialize constructor.
     *
     * @param CreateHostedCheckout $hostedCheckout
     * @param ApiErrorHandler $apiErrorHandler
     */
    public function __construct(CreateHostedCheckout $hostedCheckout, ApiErrorHandler $apiErrorHandler)
    {
        $this->hostedCheckout = $hostedCheckout;
        $this->apiErrorHandler = $apiErrorHandler;
    }

    /**
     * Trigger the initialization of the Hosted Checkout (only used for redirect payments)
     *
     * @see \Ingenico\Connect\Gateway\CanInitialize;
     *
     * @param mixed[] $commandSubject
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(array $commandSubject)
    {
        /** @var Order\Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $order = $payment->getOrder();

        try {
            $this->hostedCheckout->create($order);

            $stateObject = $commandSubject['stateObject'];
            $stateObject->setState(Order::STATE_NEW);
            $stateObject->setStatus('pending');
            $stateObject->setIsNotified(false);
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }
}
