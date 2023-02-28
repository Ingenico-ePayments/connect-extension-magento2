<?php // phpcs:ignore PSR12.Files.FileHeader.SpacingAfterBlock, SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing
/**
 * See LICENSE.md for license details.
 */

namespace Worldline\Connect\Model\Worldline\Status;

use Ingenico\Connect\Sdk\Domain\Capture\Definitions\Capture;
use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Worldline\Connect\Model\Worldline\StatusInterface;

/**
 * Class OrderStatusHelper
 *
 * @package Worldline\Connect\Model\Worldline\Connect\Status
 */
class OrderStatusHelper
{
    // phpcs:disable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    /**
     * @var int[]
     */
    // phpcs:enable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $statusesForSkipping = [800, 900, 935, 975];

    /**
     * @var array string[]
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $applicableMethods = ['card'];

    /**
     * @param Payment|Capture|AbstractOrderStatus $worldlineStatus
     * @return bool
     */
    public function shouldOrderSkipPaymentReview(AbstractOrderStatus $worldlineStatus)
    {
        return ($worldlineStatus instanceof Payment || $worldlineStatus instanceof Capture)
            && ($this->isCapturingCcMethod($worldlineStatus)
                || $worldlineStatus->status === StatusInterface::CAPTURED
                || $worldlineStatus->status === StatusInterface::PENDING_APPROVAL);
    }

    /**
     * Check conditions for CAPTURE_REQUESTED meta status
     *
     * @param Payment|Capture|AbstractOrderStatus $worldlineStatus
     * @return bool
     */
    private function isCapturingCcMethod(AbstractOrderStatus $worldlineStatus)
    {
        return ($worldlineStatus->status === StatusInterface::CAPTURE_REQUESTED
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            && in_array($this->getMethod($worldlineStatus), $this->applicableMethods, true)
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            && in_array($this->getStatusCode($worldlineStatus), $this->statusesForSkipping, true));
    }

    /**
     * Extract method string from status object
     *
     * @param Payment|Capture|AbstractOrderStatus $worldlineStatus
     * @return string
     */
    private function getMethod(AbstractOrderStatus $worldlineStatus)
    {
        $method = '';
        if ($worldlineStatus instanceof Payment) {
            $method = $worldlineStatus->paymentOutput->paymentMethod;
        } elseif ($worldlineStatus instanceof Capture) {
            $method = $worldlineStatus->captureOutput->paymentMethod;
        }

        return $method;
    }

    /**
     * Fetch legacy payment status code
     *
     * @param Payment|Capture|AbstractOrderStatus $worldlineStatus
     * @return mixed
     */
    private function getStatusCode(AbstractOrderStatus $worldlineStatus)
    {
        return $worldlineStatus->statusOutput->statusCode;
    }
}
