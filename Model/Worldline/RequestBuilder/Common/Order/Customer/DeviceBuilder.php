<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\Customer;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerDevice;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerDeviceFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\Customer\Device\BrowserDataBuilder;

class DeviceBuilder
{
    /**
     * @var CustomerDeviceFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $customerDeviceFactory;

    /**
     * @var BrowserDataBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $browserDataBuilder;

    /**
     * @var Http
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
        // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
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
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('No Accept Header set'));
        }
        return $acceptHeader;
    }
}
