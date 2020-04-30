<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model;

use Ingenico\Connect\GitHub\Dto\Release;
use Ingenico\Connect\Helper\GitHub;
use Ingenico\Connect\GitHub\Client;
use Magento\Framework\App\Cache;

use function __;
use function version_compare;

class VersionService
{
    const FLAG_KEY = 'ingenico_connect_latest_version';

    /**
     * @var GitHub
     */
    protected $gitHubHelper;

    /**
     * @var Client
     */
    protected $gitHubClient;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Cache
     */
    private $cache;

    public function __construct(
        GitHub $gitHubHelper,
        Client $gitHubClient,
        Config $configHelper,
        Cache $cache
    ) {
        $this->gitHubHelper = $gitHubHelper;
        $this->gitHubClient = $gitHubClient;
        $this->configHelper = $configHelper;
        $this->cache = $cache;
    }

    public function getCurrentVersion(): string
    {
        return $this->configHelper->getVersion();
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

        if ($currentVersion !== __('Unknown') && $latestVersion !== null
            && version_compare($currentVersion, $latestVersion, '<')) {
            return true;
        }

        return false;
    }

    private function getCachedLatestRelease(): Release
    {
        return unserialize($this->cache->load(self::FLAG_KEY));
    }

    private function hasCachedLatestRelease(): bool
    {
        $cachedLatestReleaseValue = $this->cache->load(self::FLAG_KEY);

        if (!$cachedLatestReleaseValue) {
            return false;
        }

        return unserialize($cachedLatestReleaseValue) instanceof Release;
    }

    private function storeLatestRelease(Release $latestVersion): bool
    {
        return $this->cache->save(
            serialize($latestVersion),
            self::FLAG_KEY,
            [],
            86400
        );
    }
}
