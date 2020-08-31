<?php

declare(strict_types=1);

namespace Ingenico\Connect\Observer\Refund;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class PendingApprovalObserver implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    public function __construct(ManagerInterface $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    public function execute(Observer $observer)
    {
        $this->messageManager->addNoticeMessage(
        //phpcs:ignore Generic.Files.LineLength.TooLong
            __('It appears that your account at Ingenico is configured that refunds require approval, please contact us')
        );
    }
}
