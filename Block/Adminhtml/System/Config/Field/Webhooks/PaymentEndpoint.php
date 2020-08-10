<?php

declare(strict_types=1);

namespace Ingenico\Connect\Block\Adminhtml\System\Config\Field\Webhooks;

use Ingenico\Connect\Block\Adminhtml\System\Config\Field\AbstractEndpoint;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;

class PaymentEndpoint extends AbstractEndpoint
{
    public function __construct(
        Context $context,
        UrlInterface $url,
        array $data = []
    ) {
        parent::__construct($context, $url, 'epayments/webhooks/payment', $data);
    }
}
