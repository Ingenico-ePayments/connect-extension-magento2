<?php

namespace Netresearch\Epayments\Model\Cart;

use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Item;

class Service implements ServiceInterface
{

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepostory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var string[]
     */
    private $errors = [];

    /**
     * Service constructor.
     * @param DataObjectFactory $dataObjectFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param ProductRepositoryInterface $productRepostory
     * @param SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        DataObjectFactory $dataObjectFactory,
        CartRepositoryInterface $quoteRepository,
        ProductRepositoryInterface $productRepostory,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->quoteRepository = $quoteRepository;
        $this->productRepostory = $productRepostory;
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function fillCartFromOrder(CheckoutSession $session, OrderInterface $order)
    {
        /** @var OrderItemInterface[] $items */
        $items = $order->getItems();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $session->getQuote();
        $productId = '';
        // remove items that have a parent item
        $items = array_filter(
            $items,
            function ($element) {
                /** @var OrderItemInterface|Item $element */
                return $element->getParentItemId() === null;
            }
        );
        // load products related to items in one go
        $productList = $this->loadRelatedProducts($items);

        /** @var OrderItemInterface|Item $item */
        foreach ($items as $item) {
            // generate add to cart request
            $request = $this->getProductRequest($item);

            try {
                if (array_key_exists($item->getProductId(), $productList)) {
                    $product = $productList[$item->getProductId()];
                } else {
                    $product = $item->getProduct();
                }
                $quote->addProduct($product, $request);
                $productId = $item->getProductId();
            } catch (LocalizedException $e) {
                $this->errors[] = $e->getMessage();
            }
        }
        $quote->getBillingAddress();
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        $session->setQuoteId($quote->getId());
        $session->setCartWasUpdated(true);
        $session->setLastAddedProductId($productId);
    }

    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Build product request from order item
     *
     * @param OrderItemInterface $item
     * @return \Magento\Framework\DataObject
     */
    private function getProductRequest(OrderItemInterface $item)
    {
        $requestArray = [
            'product' => $item->getProductId(),
            'qty' => $item->getQtyOrdered(),
        ];

        $type = $item->getProductType();
        $options = $item->getProductOptions();
        $info = $options['info_buyRequest'];

        switch ($type) {
            case Configurable::TYPE_CODE:
                if (!empty($info['super_attribute'])) {
                    $requestArray['super_attribute'] = $info['super_attribute'];
                }
                break;
            case Bundle::TYPE_CODE:
                if (!empty($info['bundle_option']) && !empty($info['bundle_option_qty'])) {
                    $requestArray['bundle_option'] = $info['bundle_option'];
                    $requestArray['bundle_option_qty'] = $info['bundle_option_qty'];
                }
                break;
            case Grouped::TYPE_CODE:
                if (!empty($info['super_product_config'])) {
                    $requestArray['super_product_config'] = $info['super_product_config'];
                }
                break;
        }

        return $this->dataObjectFactory->create(['data' => $requestArray]);
    }

    /**
     * Loads products related to the order items, to avoid loading in a loop
     *
     * @param OrderItemInterface[] $items
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    private function loadRelatedProducts($items)
    {
        $productIds = array_map(
            function ($item) {
                /** @var OrderItemInterface|Item $element */
                return $item->getProductId();
            },
            $items
        );
        $this->criteriaBuilder->addFilter('entity_id', $productIds, 'in');
        $productList = $this->productRepostory->getList($this->criteriaBuilder->create())->getItems();

        return $productList;
    }
}
