<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Checks;

use Exception;
use Magento\Payment\Model\Checks\SpecificationInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\PaymentMethod\PaymentMethods;

use function in_array;
use function str_starts_with;

class IsAvailableInApi implements SpecificationInterface
{
    private ?bool $testConnection = null;
    private ?array $availablePaymentProductIds = null;

    public function __construct(
        private readonly PaymentMethods $paymentMethods,
        private readonly ClientInterface $client
    ) {
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function isApplicable(MethodInterface $paymentMethod, Quote $quote)
    {
        if (!str_starts_with($paymentMethod->getCode(), 'worldline')) {
            return true;
        }

        if ($this->testConnection === null) {
            $this->testConnection = $this->testConnection((int) $quote->getStoreId());
        }

        if (!$this->testConnection) {
            return false;
        }

        $productId = $paymentMethod->getConfigData('product_id');
        if (!$productId) {
            return true;
        }

        if ($this->availablePaymentProductIds === null) {
            $this->availablePaymentProductIds = $this->paymentMethods->getAvailablePaymentProductIds($quote);
        }

        if ($this->availablePaymentProductIds !== null) {
            return in_array($productId, $this->availablePaymentProductIds);
        }

        return false;
    }

    private function testConnection(int $storeId): bool
    {
        try {
            $this->client->worldlineTestAccount($storeId);
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }
}
