<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\AdditionalInput;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderTypeInformation;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderTypeInformationFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;

class TypeInformationBuilder
{
    public const PURCHASE_TYPE_PHYSICAL = 'physical';
    public const PURCHASE_TYPE_DIGITAL = 'digital';

    public const TRANSACTION_TYPE_PURCHASE = 'purchase';
    public const TRANSACTION_TYPE_CHECK_ACCEPTANCE = 'check-acceptance';
    public const TRANSACTION_TYPE_ACCOUNT_FUNDING = 'account-funding';
    public const TRANSACTION_TYPE_QUASI_CASH = 'quasi-cash';
    public const TRANSACTION_TYPE_PREPAID_ACTIVATION_OR_LOAD = 'prepaid-activation-or-load';

    public const USAGE_TYPE_PRIVATE = 'private';
    public const USAGE_TYPE_COMMERCIAL = 'commercial';

    /**
     * @var OrderTypeInformationFactory
     */
    private $orderTypeInformationFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    public function __construct(
        OrderTypeInformationFactory $orderTypeInformationFactory,
        ScopeConfigInterface $config
    ) {
        $this->orderTypeInformationFactory = $orderTypeInformationFactory;
        $this->config = $config;
    }

    public function create(OrderInterface $order): OrderTypeInformation
    {
        $typeInformation = $this->orderTypeInformationFactory->create();

        $typeInformation->purchaseType = (bool) $order->getIsVirtual() ?
            self::PURCHASE_TYPE_DIGITAL :
            self::PURCHASE_TYPE_PHYSICAL;
        $typeInformation->usageType = self::USAGE_TYPE_COMMERCIAL;

        // For orders place in a Brazilian store this fields is required:
        if ($order->getGrandTotal() > 0 &&
            $this->config->getValue(
                'general/store_information/country_id',
                'store',
                $order->getStoreId()
            ) === 'BR'
        ) {
            $typeInformation->transactionType = self::TRANSACTION_TYPE_PURCHASE;
        }

        return $typeInformation;
    }
}
