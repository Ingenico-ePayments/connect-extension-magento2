<?php

declare(strict_types=1);

namespace Worldline\Connect\PaymentMethod;

use Exception;
use Ingenico\Connect\Sdk\Domain\Product\Definitions\PaymentProduct;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\Resolver;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\PaymentMethod;
use Magento\Quote\Model\Quote;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;

use function array_map;
use function in_array;
use function round;

class PaymentMethods
{
    public const CARDS_VAULT = 'worldline_vault';
    public const AMERICAN_EXPRESS_VAULT = 'worldline_americanexpress_vault';
    public const DISCOVER_VAULT = self::DISCOVER . '_vault';
    public const CARTE_BANCAIRE_VAULT = 'worldline_cartebancaire_vault';
    public const MASTERCARD_VAULT = 'worldline_mastercard_vault';
    public const VISA_VAULT = 'worldline_visa_vault';
    public const CARDS = 'worldline_cards';
    public const AMERICAN_EXPRESS = 'worldline_americanexpress';
    public const BC_CARD = 'worldline_bc_card';
    public const CARTE_BANCAIRE = 'worldline_cartebancaire';
    public const DINERS_CLUB = 'worldline_dinersclub';
    public const DISCOVER = 'worldline_discover';
    public const HYUNDAI_CARD = 'worldline_hyundai_card';
    public const JCB = 'worldline_jcb';
    public const KB_KOOKMIN_CARD = 'worldline_kb_kookmin_card';
    public const KEB_HANA_CARD = 'worldline_keb_hana_card';
    public const LOTTE_CARD = 'worldline_lotte_card';
    public const MASTERCARD = 'worldline_mastercard';
    public const NH_CARD = 'worldline_nh_card';
    public const SAMSUNG_CARD = 'worldline_samsung_card';
    public const SHINHAN_CARD = 'worldline_shinhan_card';
    public const UNIONPAY_EXPRESSPAY = 'worldline_unionpay_expresspay';
    public const VISA = 'worldline_visa';
    public const GIROPAY = 'worldline_giropay';
    public const IDEAL = 'worldline_ideal';
    public const OPEN_BANKING = 'worldline_open_banking';
    public const PAYPAL = 'worldline_paypal';
    public const PAYSAFECARD = 'worldline_paysafecard';
    public const SOFORT = 'worldline_sofort';
    public const TRUSTLY = 'worldline_trustly';
    public const HOSTED = 'worldline_hpp';

    public function __construct(
        private readonly ClientInterface $client,
        private readonly Resolver $resolver,
        private readonly PaymentMethodListInterface $paymentMethodList,
        private readonly Data $paymentHelper
    ) {
    }

    /**
     * @return array<MethodInterface>
     * @throws LocalizedException
     */
    public function getPaymentMethods(int $storeId): array
    {
        return array_map(function (PaymentMethod $paymentMethod) {
            return $this->paymentHelper->getMethodInstance($paymentMethod->getCode());
        }, $this->paymentMethodList->getList($storeId));
    }

    /**
     * @param array<MethodInterface> $paymentMethods
     */
    public function getPaymentMethodConfigData(array $paymentMethods, string $key): array
    {
        return array_map(
            static fn (MethodInterface $methodInstance) => $methodInstance->getConfigData($key),
            $paymentMethods
        );
    }

    /**
     * @throws LocalizedException
     */
    public function getAvailablePaymentProductIds(Quote $quote): array
    {
        /**
         * Magento overwrites the country that was set on the quote billing address.
         * @see \Magento\Quote\Model\Quote::assignCustomerWithAddressChange
         */
        $countryId = $quote->getBillingAddress()->getOrigData('country_id');
        if ($countryId === null) {
            return [];
        }

        $productIds = $this->getPaymentMethodConfigData(
            $this->getPaymentMethods((int) $quote->getStoreId()),
            'product_id'
        );

        $availableProductIds = [];
        foreach ($this->getAvailablePaymentProducts($quote, $countryId) as $product) {
            if (in_array($product->id, $productIds)) {
                $availableProductIds[] = $product->id;
            }
        }

        return $availableProductIds;
    }

    /**
     * @return array<PaymentProduct>
     */
    private function getAvailablePaymentProducts(Quote $quote, string $countryId): array
    {
        try {
            $paymentProducts = $this->client->getAvailablePaymentProducts(
                (int) round($quote->getGrandTotal() * 100),
                $quote->getBaseCurrencyCode(),
                $countryId,
                $this->resolver->getLocale(),
                $quote->getStoreId()
            );
            return $paymentProducts->paymentProducts;
        } catch (Exception $exception) {
            return [];
        }
    }
}
