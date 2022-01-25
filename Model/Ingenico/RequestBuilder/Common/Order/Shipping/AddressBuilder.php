<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Shipping;

use Ingenico\Connect\Helper\Format;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\AbstractAddressBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Shipping\Address\NameBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonal;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonalFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;

class AddressBuilder extends AbstractAddressBuilder
{
    /**
     * @var AddressPersonalFactory
     */
    private $addressPersonalFactory;

    /**
     * @var NameBuilder
     */
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
            throw new LocalizedException(__('Can not get shipping address from OrderInterface'));
        }
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress === null) {
            throw new LocalizedException(__('No shipping address available for this order'));
        }
        return $shippingAddress;
    }
}
