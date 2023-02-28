<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Gateway\Command;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Model\Worldline\Action\ApprovePayment;

class AcceptPaymentCommand implements CommandInterface
{
    /**
     * @var ApprovePayment
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $approvePayment;

    /**
     * @var ApiErrorHandler
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $apiErrorHandler;

    /**
     * WorldlineCancelCommand constructor.
     *
     * @param ApprovePayment $approvePayment
     * @param ApiErrorHandler $apiErrorHandler
     */
    public function __construct(ApprovePayment $approvePayment, ApiErrorHandler $apiErrorHandler)
    {
        $this->approvePayment = $approvePayment;
        $this->apiErrorHandler = $apiErrorHandler;
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
            $this->approvePayment->process($payment->getOrder(), null);
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }
}
