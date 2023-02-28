<?php

declare(strict_types=1);

namespace Worldline\Connect\Model;

use Magento\Framework\App\Cache;
use Worldline\Connect\GitHub\Client;
use Worldline\Connect\GitHub\Dto\Release;
use Worldline\Connect\Helper\GitHub;
use Worldline\Connect\Helper\MetaData;

use function __;
use function version_compare;

// phpcs:ignore PSR12.Files.FileHeader.SpacingAfterBlock

class VersionService
{
    public const FLAG_KEY = 'Worldline_Connect_latest_version';

    /**
     * @var GitHub
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $gitHubHelper;

    /**
     * @var Client
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $gitHubClient;

    /**
     * @var Config
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $configHelper;

    /**
     * @var MetaData
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $metaDataHelper;

    /**
     * @var Cache
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $cache;

    public function __construct(
        GitHub $gitHubHelper,
        Client $gitHubClient,
        MetaData $metaDataHelper,
        Cache $cache
    ) {
        $this->gitHubHelper = $gitHubHelper;
        $this->gitHubClient = $gitHubClient;
        $this->cache = $cache;
        $this->metaDataHelper = $metaDataHelper;
    }

    public function getCurrentVersion(): string
    {
        return $this->metaDataHelper->getModuleVersion();
    }

    public function getLatestRelease(): Release
    {
        if ($this->hasCachedLatestRelease()) {
            return $this->getCachedLatestRelease();
        }

        $latestRelease = $this->gitHubClient->getLatestRelease();
        $this->storeLatestRelease($latestRelease);

        return $latestRelease;
    }

    public function isUpdateAvailable(): bool
    {
        $currentVersion = $this->getCurrentVersion();
        $latestVersion = $this->getLatestRelease()->getTagName();

        // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine
        if ($currentVersion !== __('Unknown') && $latestVersion !== null
            // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.CloseParenthesisLine
            && version_compare($currentVersion, $latestVersion, '<')) {
            return true;
        }

        return false;
    }

    private function getCachedLatestRelease(): Release
    {
        // phpcs:ignore Generic.PHP.ForbiddenFunctions.FoundWithAlternative, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return unserialize($this->cache->load(self::FLAG_KEY));
    }

    private function hasCachedLatestRelease(): bool
    {
        $cachedLatestReleaseValue = $this->cache->load(self::FLAG_KEY);

        if (!$cachedLatestReleaseValue) {
            return false;
        }

        // phpcs:ignore Generic.PHP.ForbiddenFunctions.FoundWithAlternative, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return unserialize($cachedLatestReleaseValue) instanceof Release;
    }

    private function storeLatestRelease(Release $latestVersion): bool
    {
        return $this->cache->save(
            // phpcs:ignore Generic.PHP.ForbiddenFunctions.FoundWithAlternative, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            serialize($latestVersion),
            self::FLAG_KEY,
            [],
            86400
        );
    }
}
