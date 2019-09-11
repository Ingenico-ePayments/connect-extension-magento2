<?php

namespace Ingenico\Connect\Gateway;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\StatusResponseManager;

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
        /** @var \Magento\Sales\Model\Order\Payment $payment */
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
