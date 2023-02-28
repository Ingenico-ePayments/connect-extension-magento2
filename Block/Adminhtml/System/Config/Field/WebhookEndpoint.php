<?php

declare(strict_types=1);

namespace Worldline\Connect\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Url;

class WebhookEndpoint extends Field
{
    /**
     * @var string
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $routePath;

    /**
     * @var Url
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
