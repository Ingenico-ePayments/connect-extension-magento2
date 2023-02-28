<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Phrase;
use Worldline\Connect\Helper\MetaData;
use Worldline\Connect\Model\VersionService;

class UpdateAvailable implements MessageInterface
{
    public const MESSAGE_IDENTITY = 'connect_update_available_message';

    /** @var VersionService */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $versionService;

    /** @var MetaData */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $metaDataHelper;

    public function __construct(MetaData $metaDataHelper, VersionService $versionService)
    {
        $this->versionService = $versionService;
        $this->metaDataHelper = $metaDataHelper;
    }

    public function getIdentity(): string
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return __(
            $this->getTextTemplate(),
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
        //phpcs:ignore Generic.Files.LineLength.TooLong, SlevomatCodingStandard.Files.LineLength.LineTooLong
        return 'You are using an old version of %1 module! Version %2 has been released. Please refer to the changelog on (<a href="%3" target="_blank">GitHub</a>) for the changes and update instructions.';
    }
}
