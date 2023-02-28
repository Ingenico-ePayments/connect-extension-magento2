<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\Shipping;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonal;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonalFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Worldline\Connect\Helper\Format;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\AbstractAddressBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\Shipping\Address\NameBuilder;

class AddressBuilder extends AbstractAddressBuilder
{
    /**
     * @var AddressPersonalFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $addressPersonalFactory;

    /**
     * @var NameBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $nameBuilder;

    public function __construct(
        Format $format,
        AddressPersonalFactory $addressPersonalFactory,
        NameBuilder $nameBuilder
    ) {
        parent::__construct($format);

        $this->addressPersonalFactory = $addressPersonalFactory;
        $this->nameBuilder = $nameBuilder;
    }

    public function create(OrderInterface $order): AddressPersonal
    {
        $addressPersonal = $this->addressPersonalFactory->create();

        try {
            $shippingAddress = $this->getShippingAddressFromOrder($order);
            $this->populateAddress($addressPersonal, $shippingAddress);
            $addressPersonal->name = $this->nameBuilder->create($shippingAddress);
        // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        } catch (LocalizedException $e) {
            //Do nothing
        }

        return $addressPersonal;
    }

    /**
     * @param OrderInterface $order
     * @return Address
     * @throws LocalizedException
     */
    public function getShippingAddressFromOrder(OrderInterface $order): Address
    {
        if (!$order instanceof Order) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('Can not get shipping address from OrderInterface'));
        }
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress === null) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('No shipping address available for this order'));
        }
        return $shippingAddress;
    }
}
