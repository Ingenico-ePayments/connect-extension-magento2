<?php

declare(strict_types=1);

namespace Ingenico\Connect\PaymentMethod;

use Ingenico\Connect\Model\Ingenico\Client;
use Ingenico\Connect\Sdk\Domain\Product\PaymentProductGroups;
use Ingenico\Connect\Sdk\Domain\Product\PaymentProducts;
use Magento\Framework\Locale\Resolver;
use Magento\Quote\Model\Quote;

use function array_search;
use function round;

class PaymentMethods
{
    public const CARDS_VAULT = 'ingenico_vault';

    public const AMERICAN_EXPRESS_VAULT = 'ingenico_americanexpress_vault';
    public const CARTE_BANCAIRE_VAULT = 'ingenico_cartebancaire_vault';
    public const MASTERCARD_VAULT = 'ingenico_mastercard_vault';
    public const VISA_VAULT = 'ingenico_visa_vault';

    public const CARDS = 'ingenico_cards';

    public const AMERICAN_EXPRESS = 'ingenico_americanexpress';
    public const CARTE_BANCAIRE = 'ingenico_cartebancaire';
    public const MASTERCARD = 'ingenico_mastercard';
    public const VISA = 'ingenico_visa';

    public const GIROPAY = 'ingenico_giropay';
    public const IDEAL = 'ingenico_ideal';
    public const OPEN_BANKING = 'ingenico_open_banking';
    public const PAYPAL = 'ingenico_paypal';
    public const PAYSAFECARD = 'ingenico_paysafecard';
    public const SOFORT = 'ingenico_sofort';
    public const TRUSTLY = 'ingenico_trustly';

    public const HOSTED = 'ingenico_hpp';

    public const MAP = [
        self::CARDS => 'cards',
        self::VISA => 1,
        self::AMERICAN_EXPRESS => 2,
        self::MASTERCARD => 3,
        self::CARTE_BANCAIRE => 130,
        self::TRUSTLY => 806,
        self::IDEAL => 809,
        self::GIROPAY => 816,
        self::PAYSAFECARD => 830,
        self::SOFORT => 836,
        self::PAYPAL => 840,
        self::OPEN_BANKING => 865,
        // Vault
        self::VISA_VAULT => 1,
        self::AMERICAN_EXPRESS_VAULT => 2,
        self::MASTERCARD_VAULT => 3,
        self::CARTE_BANCAIRE_VAULT => 130,
    ];

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @param Client $client
     * @param Resolver $resolver
     */
    public function __construct(Client $client, Resolver $resolver)
    {
        $this->client = $client;
        $this->resolver = $resolver;
    }

    /**
     * @param Quote $quote
     * @return array
     */
    public function getAvailableMethods(Quote $quote): array
    {
        /**
         * Magento overwrites the country that was set on the quote billing address.
         * @see \Magento\Quote\Model\Quote::assignCustomerWithAddressChange
         */
        $countryId = $quote->getBillingAddress()->getOrigData('country_id');
        if ($countryId === null) {
            return [];
        }

        $products = [];

        foreach ($this->getAvailablePaymentProducts($quote, $countryId)->paymentProducts as $product) {
            $productCode = array_search($product->id, self::MAP);
            if ($productCode !== false) {
                $products[$productCode] = $product;
            }
        }

        foreach ($this->getPaymentProductGroups($quote, $countryId)->paymentProductGroups as $group) {
            $productCode = array_search($group->id, self::MAP);
            if ($productCode !== false) {
                $products[$productCode] = $group;
            }
        }

        return $products;
    }

    private function getAvailablePaymentProducts(Quote $quote, string $countryId): PaymentProducts
    {
        return $this->client->getAvailablePaymentProducts(
            (int) round($quote->getGrandTotal() * 100),
            $quote->getQuoteCurrencyCode(),
            $countryId,
            $this->resolver->getLocale(),
            $quote->getStoreId()
        );
    }

    private function getPaymentProductGroups(Quote $quote, string $countryId): PaymentProductGroups
    {
        return $this->client->getPaymentProductGroups(
            (int) round($quote->getGrandTotal() * 100),
            $quote->getQuoteCurrencyCode(),
            $countryId,
            $this->resolver->getLocale(),
            $quote->getStoreId()
        );
    }
}
