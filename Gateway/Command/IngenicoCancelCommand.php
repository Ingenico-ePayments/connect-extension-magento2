<?php

namespace Netresearch\Epayments\Gateway\Command;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\CommandInterface;
use Netresearch\Epayments\Model\Ingenico\Action\CancelPayment;
use Psr\Log\LoggerInterface;

class IngenicoCancelCommand extends AbstractCommand implements CommandInterface
{
    /**
     * @var CancelPayment
     */
    private $cancelPayment;

    /**
     * IngenicoCancelCommand constructor.
     *
     * @param ManagerInterface $manager
     * @param LoggerInterface $logger
     * @param CancelPayment $cancelPayment
     */
    public function __construct(
        ManagerInterface $manager,
        LoggerInterface $logger,
        CancelPayment $cancelPayment
    ) {
        $this->cancelPayment = $cancelPayment;

        parent::__construct($manager, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $commandSubject)
    {
        try {
            $this->cancelPayment->process($commandSubject['payment']->getPayment()->getOrder());
        } catch (ResponseException $e) {
            $this->handleError($e);
        }
    }
}
