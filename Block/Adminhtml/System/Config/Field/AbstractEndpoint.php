<?php

declare(strict_types=1);

namespace Ingenico\Connect\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

abstract class AbstractEndpoint extends Field
{
    /**
     * @var string
     */
    private $routePath;

    /**
     * @var UrlInterface
     */
    private $url;

    public function __construct(
        Context $context,
        UrlInterface $url,
        string $routePath,
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
        $element->setValue($this->url->getUrl($this->routePath));

        return parent::render($element);
    }
}
