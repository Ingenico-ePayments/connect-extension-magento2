<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\SalesSequence\Model\Profile;
use Magento\SalesSequence\Model\ResourceModel\Meta as MetaResource;
use Magento\SalesSequence\Model\ResourceModel\Profile as ProfileResource;
use Magento\SalesSequence\Model\Sequence;
use Magento\Store\Api\StoreRepositoryInterface;

class IncrementIdService
{
    /**
     * @var Sequence
     */
    private $sequence;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var MetaResource
     */
    private $metaResource;

    /**
     * @var ProfileResource
     */
    private $profileResource;

    public function __construct(
        Sequence $sequence,
        StoreRepositoryInterface $storeRepository,
        MetaResource $metaResource,
        ProfileResource $profileResource
    ) {
        $this->sequence = $sequence;
        $this->storeRepository = $storeRepository;
        $this->metaResource = $metaResource;
        $this->profileResource = $profileResource;
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    public function calculateMaxOrderIncrementIdLength(): int
    {
        $maxLength = 0;

        foreach ($this->storeRepository->getList() as $store) {
            $incrementId = $this->getDummyIncrementIdByStore((string) $store->getId());
            $maxLength = max($maxLength, strlen($incrementId));
        }

        return $maxLength;
    }

    /**
     * @param string $storeId
     * @return string
     * @throws LocalizedException
     */
    private function getDummyIncrementIdByStore(string $storeId): string
    {
        $sequenceProfile = $this->getSequenceProfileByStore($storeId);

        $getPattern = function () {
            return $this->pattern;
        };
        $pattern = $getPattern->call($this->sequence);

        return sprintf(
            $pattern,
            $sequenceProfile->getData('prefix'),
            '1',
            $sequenceProfile->getData('suffix')
        );
    }

    /**
     * @param string $storeId
     * @return Profile
     * @throws LocalizedException
     */
    private function getSequenceProfileByStore(string $storeId): Profile
    {
        $meta = $this->metaResource->loadByEntityTypeAndStore('order', $storeId);
        return $this->profileResource->loadActiveProfile($meta->getId());
    }
}
