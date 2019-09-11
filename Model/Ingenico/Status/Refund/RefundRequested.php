<?php

namespace Ingenico\Connect\Model\Ingenico\Status\Refund;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Ingenico\Connect\Model\Order\Creditmemo\Service;

class RefundRequested implements RefundHandlerInterface
{
    /**
     * @var Service
     */
    private $creditMemoService;

    /**
     * RefundRequested constructor.
     * @param Service $creditMemoService
     */
    public function __construct(Service $creditMemoService)
    {
        $this->creditMemoService = $creditMemoService;
    }

    /**
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws LocalizedException
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        $payment = $order->getPayment();
        /** @var Creditmemo $creditmemo */
        $creditmemo = $this->creditMemoService->getCreditmemo($payment, $ingenicoStatus->id);

        if ($creditmemo->getId()) {
            $this->applyCreditmemo($creditmemo);
        }
    }

    /**
     * @param CreditmemoInterface $creditmemo
     */
    public function applyCreditmemo(CreditmemoInterface $creditmemo)
    {
        $creditmemo->setState(Creditmemo::STATE_OPEN);
    }
}
