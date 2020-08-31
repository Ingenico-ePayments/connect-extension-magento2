<?php

declare(strict_types=1);

namespace Ingenico\Connect\Observer\Payment;

use Ingenico\Connect\Model\Ingenico\Status\Payment\Handler\AbstractHandler;
use Ingenico\Connect\Model\Order\EmailManagerFraud;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class PendingFraudApprovalObserver implements ObserverInterface
{
    /**
     * @var EmailManagerFraud
     */
    private $emailManagerFraud;

    public function __construct(
        EmailManagerFraud $emailManagerFraud
    ) {
        $this->emailManagerFraud = $emailManagerFraud;
    }

    public function execute(Observer $observer)
    {
        $this->emailManagerFraud->process(
            $observer->getData(AbstractHandler::KEY_ORDER)
        );
    }
}
