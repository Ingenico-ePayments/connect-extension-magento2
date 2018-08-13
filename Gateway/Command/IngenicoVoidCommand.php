<?php

namespace Netresearch\Epayments\Gateway\Command;

use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\CommandInterface;
use Netresearch\Epayments\Model\Ingenico\Action\UndoCapturePaymentRequest;
use Psr\Log\LoggerInterface;

class IngenicoVoidCommand extends AbstractCommand implements CommandInterface
{
    /** @var UndoCapturePaymentRequest */
    private $undoCapturePaymentRequest;

    /**
     * IngenicoVoidCommand constructor.
     *
     * @param ManagerInterface $manager
     * @param LoggerInterface $logger
     * @param UndoCapturePaymentRequest $undoCapturePaymentRequest
     */
    public function __construct(
        ManagerInterface $manager,
        LoggerInterface $logger,
        UndoCapturePaymentRequest $undoCapturePaymentRequest
    ) {
        $this->undoCapturePaymentRequest = $undoCapturePaymentRequest;

        parent::__construct($manager, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $commandSubject)
    {
        $this->undoCapturePaymentRequest->process($commandSubject['payment']->getPayment()->getOrder());
    }
}
