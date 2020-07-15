<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\System\Message;

use Ingenico\Connect\Helper\MetaData;
use Ingenico\Connect\Model\VersionService;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Phrase;

class UpdateAvailable implements MessageInterface
{
    const MESSAGE_IDENTITY = 'connect_update_available_message';
    
    /** @var VersionService */
    private $versionService;
    
    /** @var MetaData */
    private $metaDataHelper;
    
    public function __construct(MetaData $metaDataHelper, VersionService $versionService)
    {
        $this->versionService = $versionService;
        $this->metaDataHelper = $metaDataHelper;
    }

    public function getIdentity(): string
    {
        return md5(self::MESSAGE_IDENTITY);
    }

    public function isDisplayed(): bool
    {
        return $this->versionService->isUpdateAvailable();
    }

    /**
     * @return Phrase|string
     */
    public function getText()
    {
        $latestRelease = $this->versionService->getLatestRelease();

        return __(
            $this->getTextTemplate(),
            str_replace('_', ' ', $this->metaDataHelper->getModuleName()),
            $latestRelease->getTagName(),
            $latestRelease->getUrl()
        );
    }

    public function getSeverity(): int
    {
        return self::SEVERITY_NOTICE;
    }

    private function getTextTemplate(): string
    {
        //phpcs:ignore Generic.Files.LineLength.TooLong
        return '%1 version %2 has been released. Please refer to the changelog on (<a href="%3" target="_blank">GitHub</a>) for the changes and update instructions.';
    }
}
