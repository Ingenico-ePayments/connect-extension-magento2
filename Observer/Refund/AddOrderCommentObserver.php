<?php

declare(strict_types=1);

namespace Ingenico\Connect\Observer\Refund;

use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Status\Refund\Handler\AbstractHandler;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Creditmemo;

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
        $creditMemo = $observer->getData(AbstractHandler::KEY_CREDIT_MEMO);
        /** @var RefundResult $ingenicoStatus */
        $ingenicoStatus = $observer->getData(AbstractHandler::KEY_INGENICO_STATUS);
        $status = $ingenicoStatus->status;
        if ($status !== null && $creditMemo instanceof Creditmemo) {
            $creditMemo->addComment(implode(
                '. ',
                [
                    __($this->config->getRefundStatusInfo($status)),
                    __('Status: %1', $status),
                ]
            ));
        }
    }
}
