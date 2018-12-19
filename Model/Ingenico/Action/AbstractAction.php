<?php

namespace Netresearch\Epayments\Model\Ingenico\Action;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\StatusResponseManager;
use Netresearch\Epayments\Model\Transaction\TransactionManager;

abstract class AbstractAction
{
    /**
     * @var StatusResponseManager
     */
    protected $statusResponseManager;

    /**
     * @var ClientInterface
     */
    protected $ingenicoClient;

    /**
     * @var TransactionManager
     */
    protected $transactionManager;

    /**
     * @var ConfigInterface
     */
    protected $ePaymentsConfig;

    /**
     * AbstractAction constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $config
    ) {
        $this->statusResponseManager = $statusResponseManager;
        $this->ingenicoClient = $ingenicoClient;
        $this->transactionManager = $transactionManager;
        $this->ePaymentsConfig = $config;
    }

    /**
     * @param Payment $payment
     * @param \Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment
     * | \Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse
     * | \Ingenico\Connect\Sdk\Domain\Refund\RefundResponse $response
     * @throws LocalizedException
     */
    protected function postProcess(
        Payment $payment,
        $response
    ) {
        $payment->setTransactionId($response->id);
        $this->statusResponseManager->set($payment, $response->id, $response);
    }
}
