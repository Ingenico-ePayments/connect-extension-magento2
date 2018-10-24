<?php

namespace Netresearch\Epayments\Gateway\Command;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\Ingenico\Action\CreateHostedCheckout;
use Netresearch\Epayments\Model\Ingenico\Action\CreatePayment;
use Psr\Log\LoggerInterface;

class Initialize extends AbstractCommand implements CommandInterface
{
    /**
     * @var CreateHostedCheckout
     */
    private $hostedCheckout;

    /**
     * @var CreatePayment
     */
    private $createPayment;

    /**
     * Initialize constructor.
     *
     * @param ManagerInterface $manager
     * @param LoggerInterface $logger
     * @param CreateHostedCheckout $hostedCheckout
     * @param CreatePayment $createPayment
     */
    public function __construct(
        ManagerInterface $manager,
        LoggerInterface $logger,
        CreateHostedCheckout $hostedCheckout,
        CreatePayment $createPayment
    ) {
        $this->hostedCheckout = $hostedCheckout;
        $this->createPayment = $createPayment;

        parent::__construct($manager, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $commandSubject)
    {
        /** @var Order\Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $order = $payment->getOrder();

        try {
            if ($payment->getAdditionalInformation(Config::CLIENT_PAYLOAD_KEY)) {
                $this->createPayment->create($order);
                /** Delete the payload after we are done with it. */
                $payment->setAdditionalInformation(Config::CLIENT_PAYLOAD_KEY, null);
            } else {
                $this->hostedCheckout->create($order);
            }

            $stateObject = $commandSubject['stateObject'];
            $stateObject->setState(
                $order->getState() === Order::STATE_NEW ? Order::STATE_PENDING_PAYMENT : $order->getState()
            );
            $stateObject->setStatus(
                $order->getStatus() === Order::STATE_NEW ? Order::STATE_PENDING_PAYMENT : $order->getStatus()
            );
            $stateObject->setIsNotified(false);
        } catch (ResponseException $e) {
            $this->handleError($e);
        }
    }
}
