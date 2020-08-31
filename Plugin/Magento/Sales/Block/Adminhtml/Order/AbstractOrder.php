<?php

declare(strict_types=1);

namespace Ingenico\Connect\Plugin\Magento\Sales\Block\Adminhtml\Order;

use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\ConfigProvider;
use Magento\Sales\Api\Data\OrderInterface;

abstract class AbstractOrder
{
    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    protected function allowOfflineRefund(OrderInterface $order): bool
    {
        return
            $order->getPayment()->getMethod() !== ConfigProvider::CODE ||
            $this->config->allowOfflineRefunds();
    }
}
