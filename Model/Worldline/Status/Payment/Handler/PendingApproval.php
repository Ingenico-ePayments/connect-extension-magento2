<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Worldline\Connect\Helper\Data;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;
use Worldline\Connect\Model\Worldline\Token\TokenService;

class PendingApproval extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'pending_approval';

    /**
     * @var TokenService
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $tokenService;

    public function __construct(ManagerInterface $eventManager, ConfigInterface $config, TokenService $tokenService)
    {
        parent::__construct($eventManager, $config);

        $this->tokenService = $tokenService;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $worldlineStatus)
    {
        /** @var OrderPaymentInterface|Order\Payment $payment */
        $payment = $order->getPayment();
        $amount = $worldlineStatus->paymentOutput->amountOfMoney->amount;
        $amount = Data::reformatMagentoAmount($amount);

        $payment->setIsTransactionClosed(false);
        $payment->setIsTransactionPending(true);

        if ($order->getStatus() === 'pending') {
            // If the order is in "pending" status (for example, after a challenged authorize)
            // We need to call the "authorize()"-method directly, otherwise the order state doesn't get updated:
            $payment->authorize(false, $amount);
        }

//        $payment->registerAuthorizationNotification($amount);
//

        $this->dispatchEvent($order, $worldlineStatus);

        if ($order instanceof Order) {
            $this->tokenService->createByOrderAndPayment($order, $worldlineStatus);
        }
    }

    /**
     * Perform payment action registration on payment object depending on config settings.
     * Client payload key is only set when inline checkout is configured for a specific payment product.
     *
     * @param OrderInterface $order
     * @param $amount
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    private function registerPaymentNotification(OrderInterface $order, $amount)
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();
        try {
            if (!$payment->getAdditionalInformation(Config::CLIENT_PAYLOAD_KEY)) {
                /**
                 * Only register something, if we have some sort of hosted checkout, otherwise we are in inline flow
                 * and Magento will handle the workflow itself.
                 *
                 * @see Payment::place()
                 */
                if ($payment->getMethodInstance()->getConfigPaymentAction() === AbstractMethod::ACTION_AUTHORIZE) {
                    $payment->registerCaptureNotification($amount);
                } else {
                    $payment->registerAuthorizationNotification($amount);
                }
            }
            // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        } catch (LocalizedException $e) {
        }
    }
}
