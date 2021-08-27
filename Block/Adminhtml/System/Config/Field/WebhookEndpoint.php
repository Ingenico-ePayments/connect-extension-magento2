<?php

declare(strict_types=1);

namespace Ingenico\Connect\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Url;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class WebhookEndpoint extends Field
{
    /**
     * @var string
     */
    private $routePath;

    /**
     * @var Url
     */
    private $url;

    public function __construct(
        Context $context,
        Url $url,
        string $routePath = 'epayments/webhooks',
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->url = $url;
        $this->routePath = $routePath;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->setValue($this->getWebhookUrl());

        return parent::render($element);
    }

    public function getWebhookUrl(): string
    {
        return $this->url->getUrl($this->routePath);
    }
}
