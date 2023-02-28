<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\Customer\Account;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerAccountAuthentication;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerAccountAuthenticationFactory;
use Magento\Customer\Model\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Sales\Api\Data\OrderInterface;

class AuthenticationBuilder
{
    public const GUEST = 'guest';
    public const MERCHANT_CREDENTIALS = 'merchant-credentials';

    /**
     * @var CustomerAccountAuthenticationFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $authenticationFactory;

    /**
     * @var Logger
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $customerLogger;

    /**
     * @var DateTimeFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $dateTimeFactory;

    public function __construct(
        CustomerAccountAuthenticationFactory $authenticationFactory,
        Logger $customerLogger,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->authenticationFactory = $authenticationFactory;
        $this->customerLogger = $customerLogger;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    public function create(OrderInterface $order): CustomerAccountAuthentication
    {
        /** @var CustomerAccountAuthentication $authentication */
        $authentication = $this->authenticationFactory->create();

        $authentication->method = $this->getAuthenticationMethod($order);

        try {
            $authentication->utcTimestamp = $this->getAuthenticationUtcTimestamp($order);
        // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        } catch (LocalizedException $exception) {
            // Do nothing
        }

        return $authentication;
    }

    private function getAuthenticationMethod(OrderInterface $order): string
    {
        return $order->getCustomerIsGuest() ? self::GUEST : self::MERCHANT_CREDENTIALS;
    }

    /**
     * @param OrderInterface $order
     * @return string
     * @throws LocalizedException
     */
    private function getAuthenticationUtcTimestamp(OrderInterface $order): string
    {
        if ($order->getCustomerIsGuest() || !$order->getCustomerId()) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('Cannot get customer last login time'));
        }

        return $this->dateTimeFactory
            ->create($this->customerLogger->get($order->getCustomerId())->getLastLoginAt())
            ->format('YmdHi');
    }
}
