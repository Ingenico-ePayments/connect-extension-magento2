<?php

namespace Ingenico\Connect\Test\Integration\Fixture;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

class Product
{
    const SKU_SIMPLE_PRODUCT = 'SIMPLE-001';

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @return MagentoProduct
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function createProduct(string $sku = null): MagentoProduct
    {
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        /** @var StockRegistryInterface $stockRegistry */
        $stockRegistry = $this->objectManager->get(StockRegistryInterface::class);

        /** @var EavConfig $eavConfig */
        $eavConfig = $this->objectManager->get(EavConfig::class);

        /** @var MagentoProduct $product */
        $product = $this->objectManager->create(MagentoProduct::class);
        $product->setName('Simple Product #1');
        $product->setTypeId('simple');
        $product->setSku(self::SKU_SIMPLE_PRODUCT);
        $product->setPrice(19.95);
        $product->setStatus(Status::STATUS_ENABLED);
        $product->setVisibility(Visibility::VISIBILITY_BOTH);
        $product->setAttributeSetId($eavConfig->getEntityType(MagentoProduct::ENTITY)->getDefaultAttributeSetId());
        if ($sku !== null) {
            $product->setName($sku);
            $product->setSku($sku);
        }
        $product = $productRepository->save($product);

        $stockItem = $stockRegistry->getStockItemBySku($product->getSku());
        $stockItem->setQty(10000);
        $stockItem->setIsInStock(true);
        $stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);

        return $product;
    }
}
