<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model;

use function __;
use Ingenico\Connect\GitHub\Dto\Release;
use Ingenico\Connect\Helper\GitHub;
use Ingenico\Connect\GitHub\Client;
use function is_string;
use Magento\Framework\FlagManager;
use function version_compare;

class VersionService
{
    const FLAG_KEY = 'ingenico_connect_latest_version';
    
    /** @var FlagManager */
    protected $flagManager;
    
    /** @var GitHub */
    protected $gitHubHelper;
    
    /** @var Client */
    protected $gitHubClient;
    
    /** @var Config */
    protected $configHelper;
    
    public function __construct(
        FlagManager $flagManager,
        GitHub $gitHubHelper,
        Client $gitHubClient,
        Config $configHelper
    ) {
        $this->flagManager = $flagManager;
        $this->gitHubHelper = $gitHubHelper;
        $this->gitHubClient = $gitHubClient;
        $this->configHelper = $configHelper;
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
    
    public function updateLatestRelease(): bool
    {
        $latestRelease = $this->gitHubClient->getLatestRelease();
    
        return $this->storeLatestRelease($latestRelease);
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
        return unserialize($this->flagManager->getFlagData(self::FLAG_KEY));
    }
    
    private function hasCachedLatestRelease(): bool
    {
        $cachedLatestReleaseValue = $this->flagManager->getFlagData(self::FLAG_KEY);
        if (!is_string($cachedLatestReleaseValue)) {
            return false;
        }
        
        return unserialize($cachedLatestReleaseValue) instanceof Release;
    }
    
    private function storeLatestRelease(Release $latestVersion): bool
    {
        return $this->flagManager->saveFlag(self::FLAG_KEY, serialize($latestVersion));
    }
}
