<?php

declare(strict_types=1);

namespace Worldline\Connect\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Phrase;
use Worldline\Connect\Model\ConfigInterface;

use function __;
use function sprintf;

class LogAllRequests extends Field
{
    /**
     * @param Context $context
     * @param ConfigInterface $config
     * @param File $fileDriver
     * @param array $data
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly File $fileDriver,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element): string
    {
        $logFile = $this->config->getLogAllRequestsFile();
        if ($this->fileDriver->isExists($logFile)) {
            $downloadLink = $this->getLinkToDownloadLogFile();
            $element->setComment($this->getPhrase($downloadLink));
        }

        return (string) parent::render($element);
    }

    private function getLinkToDownloadLogFile(): string
    {
        return (string) $this->getUrl('epayments/downloadLogFile/index');
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    private function getPhrase($downloadLink): Phrase
    {
        return __(sprintf('Download %s', '<a href="' . $downloadLink . '">log file</a>'));
    }
}
