<?php

namespace Netresearch\Epayments\Gateway\Command;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\CommandInterface;
use Netresearch\Epayments\Model\Ingenico\Action\Refund\CreateRefund;
use Psr\Log\LoggerInterface;

class IngenicoRefundCommand extends AbstractCommand implements CommandInterface
{
    /**
     * @var CreateRefund
     */
    private $createRefund;

    /**
     * IngenicoRefundCommand constructor.
     *
     * @param CreateRefund $createRefund
     * @param ManagerInterface $manager
     * @param LoggerInterface $logger
     */
    public function __construct(
        CreateRefund $createRefund,
        ManagerInterface $manager,
        LoggerInterface $logger
    ) {
        $this->createRefund = $createRefund;

        parent::__construct($manager, $logger);
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
            $this->handleError($e);
        }
    }
}
