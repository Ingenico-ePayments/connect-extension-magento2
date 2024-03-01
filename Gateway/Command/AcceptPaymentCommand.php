<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Gateway\Command;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Model\Worldline\Action\CapturePayment;

class AcceptPaymentCommand implements CommandInterface
{
    /**
     * @var ApiErrorHandler
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $apiErrorHandler;

    /**
     * @var CapturePayment
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $capturePayment;

    /**
     * @param ApiErrorHandler $apiErrorHandler
     * @param CapturePayment $capturePayment
     */
    public function __construct(ApiErrorHandler $apiErrorHandler, CapturePayment $capturePayment)
    {
        $this->apiErrorHandler = $apiErrorHandler;
        $this->capturePayment = $capturePayment;
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName, SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    /**
     * @param mixed [] $commandSubject
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName, SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    public function execute(array $commandSubject)
    {
        try {
            /** @var Payment $payment */
            $payment = $commandSubject['payment']->getPayment();
            $this->capturePayment->process($payment, $payment->getOrder()->getBaseGrandTotal());
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }
}
