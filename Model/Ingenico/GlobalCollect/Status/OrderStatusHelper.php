<?php
/**
 * See LICENSE.md for license details.
 */

namespace Ingenico\Connect\Model\Ingenico\GlobalCollect\Status;

use Ingenico\Connect\Sdk\Domain\Capture\Definitions\Capture;
use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Ingenico\Connect\Model\Ingenico\StatusInterface;

/**
 * Class OrderStatusHelper
 *
 * @package Ingenico\Connect\Model\Ingenico\GlobalCollect\Status
 */
class OrderStatusHelper
{
    /**
     * @var int[]
     */
    private $statusesForSkipping = [800, 900, 935, 975];

    /**
     * @var array string[]
     */
    private $applicableMethods = ['card'];

    /**
     * @param Payment|Capture|AbstractOrderStatus $ingenicoStatus
     * @return bool
     */
    public function shouldOrderSkipPaymentReview(AbstractOrderStatus $ingenicoStatus)
    {
        return ($ingenicoStatus instanceof Payment || $ingenicoStatus instanceof Capture)
            && ($this->isCapturingCcMethod($ingenicoStatus)
                || $ingenicoStatus->status === StatusInterface::CAPTURED
                || $ingenicoStatus->status === StatusInterface::PENDING_APPROVAL);
    }

    /**
     * Check conditions for CAPTURE_REQUESTED meta status
     *
     * @param Payment|Capture|AbstractOrderStatus $ingenicoStatus
     * @return bool
     */
    private function isCapturingCcMethod(AbstractOrderStatus $ingenicoStatus)
    {
        return ($ingenicoStatus->status === StatusInterface::CAPTURE_REQUESTED
            && in_array($this->getMethod($ingenicoStatus), $this->applicableMethods, true)
            && in_array($this->getStatusCode($ingenicoStatus), $this->statusesForSkipping, true));
    }

    /**
     * Extract method string from status object
     *
     * @param Payment|Capture|AbstractOrderStatus $ingenicoStatus
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

    /**
     * Fetch legacy payment status code
     *
     * @param Payment|Capture|AbstractOrderStatus $ingenicoStatus
     * @return mixed
     */
    private function getStatusCode(AbstractOrderStatus $ingenicoStatus)
    {
        return $ingenicoStatus->statusOutput->statusCode;
    }
}
