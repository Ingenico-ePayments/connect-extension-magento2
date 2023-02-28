<?php

declare(strict_types=1);

namespace Worldline\Connect\GitHub;

use Exception;
use Magento\Framework\HTTP\ClientInterfaceFactory;
use Psr\Log\LoggerInterface;
use Worldline\Connect\GitHub\Dto\Builder\ReleaseBuilder;
use Worldline\Connect\GitHub\Dto\Release;
use Worldline\Connect\GitHub\Dto\ReleaseFactory;
use Worldline\Connect\Helper\GitHub;
use Worldline\Connect\Helper\MetaData;

use function is_object;
use function json_decode;
use function sprintf;

// phpcs:ignore PSR12.Files.FileHeader.SpacingAfterBlock

class Client
{
    /** @var GitHub */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $gitHubHelper;

    /** @var ClientInterfaceFactory */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $clientFactory;

    /** @var ReleaseBuilder */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $releaseBuilder;

    /** @var ReleaseFactory */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $releaseFactory;

    /** @var MetaData */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $metaDataHelper;

    /** @var LoggerInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
                // phpcs:ignore SlevomatCodingStandard.Arrays.TrailingArrayComma.MissingTrailingComma
                'trace' => $exception->getTraceAsString()
            ]);

            return $this->releaseFactory->create();
        }
    }
}
