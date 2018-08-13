<?php

namespace Netresearch\Epayments\Gateway;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\StatusResponseManager;

abstract class AbstractValueHandler implements \Magento\Payment\Gateway\Config\ValueHandlerInterface
{
    /**
     * @var StatusResponseManager
     */
    private $statusResponseManager;

    /**
     * AbstractValueHandler constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     */
    public function __construct(
        StatusResponseManager $statusResponseManager
    ) {
        $this->statusResponseManager = $statusResponseManager;
    }

    /**
     * @param array $subject
     * @param null $storeId
     * @return bool
     */
    public function handle(array $subject, $storeId = null)
    {
        $result = false;
        $payment = $subject['payment']->getPayment();
        $paymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);

        $paymentResponse = $this->statusResponseManager->get($payment, $paymentId);

        if ($paymentResponse) {
            $result = $this->getResponseValue($paymentResponse);
        }

        return $result;
    }

    /**
     * @param Payment $paymentResponse
     * @return bool
     */
    protected function getResponseValue($paymentResponse)
    {
        /** Override in subclass */
        return false;
    }
}
