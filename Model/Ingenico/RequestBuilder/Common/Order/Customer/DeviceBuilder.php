<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\Device\BrowserDataBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerDevice;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerDeviceFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

class DeviceBuilder
{
    /**
     * @var CustomerDeviceFactory
     */
    private $customerDeviceFactory;

    /**
     * @var BrowserDataBuilder
     */
    private $browserDataBuilder;

    /**
     * @var Http
     */
    private $request;

    public function __construct(
        CustomerDeviceFactory $customerDeviceFactory,
        BrowserDataBuilder $browserDataBuilder,
        Http $request
    ) {
        $this->customerDeviceFactory = $customerDeviceFactory;
        $this->browserDataBuilder = $browserDataBuilder;
        $this->request = $request;
    }

    public function create(OrderInterface $order): CustomerDevice
    {
        $customerDevice = $this->customerDeviceFactory->create();
        $customerDevice->browserData = $this->browserDataBuilder->create();

        try {
            $customerDevice->acceptHeader = $this->getAcceptHeader();
        } catch (LocalizedException $exception) {
            // Do nothing
        }

        $customerDevice->ipAddress = $order->getRemoteIp();

        return $customerDevice;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    private function getAcceptHeader(): string
    {
        $acceptHeader = $this->request->getHeader('Accept');
        if (!$acceptHeader) {
            throw new LocalizedException(__('No Accept Header set'));
        }
        return $acceptHeader;
    }
}
