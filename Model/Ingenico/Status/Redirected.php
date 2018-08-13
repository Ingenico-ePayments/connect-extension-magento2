<?php

namespace Netresearch\Epayments\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\Order\EmailManager;

class Redirected implements HandlerInterface
{

    /**
     * @var EmailManager
     */
    private $orderEMailManager;

    /**
     * Redirected constructor.
     * @param EmailManager $orderEMailManager
     */
    public function __construct(EmailManager $orderEMailManager)
    {
        $this->orderEMailManager = $orderEMailManager;
    }

    /**
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus $ingenicoStatus
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        $this->orderEMailManager->process($order, $ingenicoStatus->status);
    }
}
