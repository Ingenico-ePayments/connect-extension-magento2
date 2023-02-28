<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\Customer;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonal;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonalFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Worldline\Connect\Helper\Format;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\AbstractAddressBuilder;

class AddressBuilder extends AbstractAddressBuilder
{
    /**
     * @var AddressPersonalFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $addressPersonalFactory;

    public function __construct(Format $format, AddressPersonalFactory $addressPersonalFactory)
    {
        parent::__construct($format);

        $this->addressPersonalFactory = $addressPersonalFactory;
    }

    public function create(OrderInterface $order): AddressPersonal
    {
        $addressPersonal = $this->addressPersonalFactory->create();

        try {
            $billingAddress = $this->getBillingAddressFromOrder($order);
            $this->populateAddress($addressPersonal, $billingAddress);
        // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        } catch (LocalizedException $e) {
            //Do nothing
        }

        return $addressPersonal;
    }

    /**
     * @throws LocalizedException
     */
    public function getBillingAddressFromOrder(OrderInterface $order): OrderAddressInterface
    {
        $billingAddress = $order->getBillingAddress();
        if ($billingAddress === null) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('No billing address available for this order'));
        }
        return $billingAddress;
    }
}
