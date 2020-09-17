<?php

declare(strict_types=1);

namespace Ingenico\Connect\Block\Adminhtml\System\Config\Field\Webhooks;

use Ingenico\Connect\Block\Adminhtml\System\Config\Field\AbstractEndpoint;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Url;

class RefundEndpoint extends AbstractEndpoint
{
    public function __construct(
        Context $context,
        Url $url,
        array $data = []
    ) {
        parent::__construct($context, $url, 'epayments/webhooks/refund', $data);
    }
}
