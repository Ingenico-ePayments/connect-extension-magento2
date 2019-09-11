<?php

namespace Ingenico\Connect\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Filesystem\Driver\File;
use Ingenico\Connect\Model\ConfigInterface;

class LogAllRequests extends Field
{
    /** @var ConfigInterface */
    private $config;

    /** @var File */
    private $fileDriver;

    /**
     * @param Context $context
     * @param ConfigInterface $config
     * @param File $fileDriver
     * @param array $data
     */
    public function __construct(Context $context, ConfigInterface $config, File $fileDriver, array $data = [])
    {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->fileDriver = $fileDriver;
    }

    /**
     * Displays link to download logging file
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $logFile = $this->config->getLogAllRequestsFile();
        if ($this->fileDriver->isExists($logFile)) {
            $downloadLink = $this->getLinkToDownloadLogFile();
            $element->setComment($this->getPhrase($downloadLink));
        }

        return parent::render($element);
    }

    /**
     * Builds a link to download logging file
     *
     * @return string
     */
    private function getLinkToDownloadLogFile()
    {
        return $this->getUrl('epayments/downloadLogFile/index');
    }

    /**
     * Constructs translatable phrase to provide download link
     *
     * @param $downloadLink
     * @return \Magento\Framework\Phrase
     */
    private function getPhrase($downloadLink)
    {
        return __(sprintf('Download %s', '<a href="' . $downloadLink . '">log file</a>'));
    }
}
