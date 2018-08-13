<?php

namespace Netresearch\Epayments\Gateway;

class IsRedirect implements \Magento\Payment\Gateway\Config\ValueHandlerInterface
{
    /**
     * This stops the core from sending an order update
     * email before the external checkout flow is finished
     *
     * @param array $subject
     * @param null $storeId
     * @return bool
     */
    public function handle(array $subject, $storeId = null)
    {
        return true;
    }
}
