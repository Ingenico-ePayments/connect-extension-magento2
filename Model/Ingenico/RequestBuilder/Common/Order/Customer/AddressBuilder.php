<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer;

use Ingenico\Connect\Helper\Format;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\AbstractAddressBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonal;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonalFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;

class AddressBuilder extends AbstractAddressBuilder
{
    /**
     * @var AddressPersonalFactory
     */
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
            throw new LocalizedException(__('No billing address available for this order'));
        }
        return $billingAddress;
    }
}
