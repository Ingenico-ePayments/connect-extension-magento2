<?php

declare(strict_types=1);

namespace Ingenico\Connect\GitHub;

use Exception;
use Ingenico\Connect\GitHub\Dto\Builder\ReleaseBuilder;
use Ingenico\Connect\GitHub\Dto\ReleaseFactory;
use Ingenico\Connect\GitHub\Dto\Release;
use Ingenico\Connect\Helper\GitHub;
use Ingenico\Connect\Helper\MetaData;
use function is_object;
use Magento\Framework\HTTP\ClientInterfaceFactory;
use Psr\Log\LoggerInterface;
use function json_decode;
use function sprintf;

class Client
{
    /** @var GitHub */
    private $gitHubHelper;
    
    /** @var ClientInterfaceFactory */
    private $clientFactory;
    
    /** @var ReleaseBuilder */
    private $releaseBuilder;
    
    /** @var ReleaseFactory */
    private $releaseFactory;
    
    /** @var MetaData */
    private $metaDataHelper;
    
    /** @var LoggerInterface */
    private $logger;
    
    public function __construct(
        GitHub $gitHubHelper,
        ClientInterfaceFactory $clientFactory,
        ReleaseBuilder $releaseBuilder,
        ReleaseFactory $releaseFactory,
        MetaData $metaDataHelper,
        LoggerInterface $logger
    ) {
        $this->gitHubHelper = $gitHubHelper;
        $this->clientFactory = $clientFactory;
        $this->releaseBuilder = $releaseBuilder;
        $this->releaseFactory = $releaseFactory;
        $this->metaDataHelper = $metaDataHelper;
        $this->logger = $logger;
    }
    
    public function getLatestRelease(): Release
    {
        try {
            $client = $this->clientFactory->create();
            $client->addHeader('User-Agent', $this->metaDataHelper->getExtensionCreator());
            $client->get(sprintf('%s/releases/latest', $this->gitHubHelper->getApiUrl()));
            $latestReleaseObject = json_decode($client->getBody());
            
            if (!is_object($latestReleaseObject)) {
                return $this->releaseFactory->create();
            }
            
            return $this->releaseBuilder->buildFromObject($latestReleaseObject);
        } catch (Exception $exception) {
            $this->logger->warning('Exception occurred during latest version api request.', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

            return $this->releaseFactory->create();
        }
    }
}
