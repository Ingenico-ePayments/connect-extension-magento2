<?php

namespace Netresearch\Epayments\Plugin\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Capture\Definitions\Capture;
use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Api\Data\OrderInterface;
use Netresearch\Epayments\Model\Ingenico\Status\CapturedFactory;
use Netresearch\Epayments\Model\Ingenico\Status\CaptureRequested;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;

class CaptureRequestedPlugin
{
    /**
     * @var CapturedFactory
     */
    private $captureFactory;

    private $codesToOverwrite = [800, 900, 975];

    /**
     * CaptureRequestedPlugin constructor.
     * @param CapturedFactory $captureFactory
     */
    public function __construct(CapturedFactory $captureFactory)
    {
        $this->captureFactory = $captureFactory;
    }

    /**
     * Rewires the CAPTURE_REQUESTED status for GC CC to CAPTURED to be able to release goods sooner
     *
     * @param CaptureRequested $subject
     * @param $proceed
     * @param OrderInterface $order
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function aroundResolveStatus(
        CaptureRequested $subject,
        $proceed,
        OrderInterface $order,
        AbstractOrderStatus $ingenicoStatus
    ) {
        if ($this->shouldReplaceHandler($ingenicoStatus)) {
            $capturedHandler = $this->captureFactory->create();
            $capturedHandler->resolveStatus($order, $ingenicoStatus);
        } else {
            $proceed($order, $ingenicoStatus);
        }
    }

    /**
     * @param AbstractOrderStatus $ingenicoStatus
     * @return bool
     */
    private function shouldReplaceHandler(AbstractOrderStatus $ingenicoStatus)
    {
        return ($ingenicoStatus instanceof Payment || $ingenicoStatus instanceof Capture)
               && $this->getMethod($ingenicoStatus) === 'card'
               && $ingenicoStatus->status === StatusInterface::CAPTURE_REQUESTED
               && in_array($ingenicoStatus->statusOutput->statusCode, $this->codesToOverwrite, true);
    }

    /**
     * Extract method string from status object
     *
     * @param AbstractOrderStatus $ingenicoStatus
     * @return string
     */
    private function getMethod(AbstractOrderStatus $ingenicoStatus)
    {
        $method = '';
        if ($ingenicoStatus instanceof Payment) {
            $method = $ingenicoStatus->paymentOutput->paymentMethod;
        } elseif ($ingenicoStatus instanceof Capture) {
            $method = $ingenicoStatus->captureOutput->paymentMethod;
        }

        return $method;
    }
}
