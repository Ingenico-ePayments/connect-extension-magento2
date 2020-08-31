<?php

declare(strict_types=1);

namespace Ingenico\Connect\Observer\Payment;

use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Status\Payment\Handler\AbstractHandler;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class AddOrderCommentObserver implements ObserverInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getData(AbstractHandler::KEY_ORDER);
        /** @var Payment $ingenicoStatus */
        $ingenicoStatus = $observer->getData(AbstractHandler::KEY_INGENICO_STATUS);
        $status = $ingenicoStatus->status;
        if ($status !== null && $order instanceof Order) {
            $order->addCommentToStatusHistory(
                implode(
                    '. ',
                    [
                        __($this->config->getPaymentStatusInfo($status)),
                        __('Status: %1', $status),
                    ]
                )
            );
        }
    }
}
