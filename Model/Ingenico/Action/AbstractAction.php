<?php

namespace Ingenico\Connect\Model\Ingenico\Action;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\StatusResponseManager;
use Ingenico\Connect\Model\Transaction\TransactionManager;

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
