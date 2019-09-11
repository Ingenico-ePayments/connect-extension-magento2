<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\DecoratorInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput\Card\ThreeDSecureBuilder;
use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CardPaymentMethodSpecificInputFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CardRecurrenceDetailsFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class CardDecorator
 */
class CardDecorator implements DecoratorInterface
{
    const TRANSACTION_CHANNEL = 'ECOMMERCE';
    const UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_FIRST = 'first';
    const UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_SUBSEQUENT = 'subsequent';
    const UNSCHEDULED_CARD_ON_FILE_REQUESTOR_CARDHOLDER_INITIATED = 'cardholderInitiated';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var CardPaymentMethodSpecificInputFactory
     */
    private $cardPaymentMethodSpecificInputFactory;

    /**
     * @var ThreeDSecureBuilder
     */
    private $threeDSecureBuilder;

    /**
     * @var CardRecurrenceDetailsFactory
     */
    private $cardRecurrenceDetailsFactory;

    public function __construct(
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        CardRecurrenceDetailsFactory $cardRecurrenceDetailsFactory,
        ThreeDSecureBuilder $threeDSecureBuilder,
        ConfigInterface $config
    ) {
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->cardRecurrenceDetailsFactory = $cardRecurrenceDetailsFactory;
        $this->threeDSecureBuilder = $threeDSecureBuilder;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        $input = $this->cardPaymentMethodSpecificInputFactory->create();
        $input->recurring = $this->cardRecurrenceDetailsFactory->create();
        $input->threeDSecure = $this->threeDSecureBuilder->create($order);
        $input->transactionChannel = self::TRANSACTION_CHANNEL;
        $input->paymentProductId = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);

        // Retrieve capture mode from config
        $captureMode = $this->config->getCaptureMode($order->getStoreId());
        $input->requiresApproval = (
            $captureMode === Config::CONFIG_INGENICO_CAPTURES_MODE_AUTHORIZE
        );

        if (!$order->getCustomerIsGuest() && $order->getCustomerId()) {
            $input->tokenize = (int) $order->getPayment()->getAdditionalInformation(Config::PRODUCT_TOKENIZE_KEY) === 1;
        } else {
            $input->tokenize = false;
        }
        try {
            $input->unscheduledCardOnFileSequenceIndicator =
                $this->getUnscheduledCardOnFileSequenceIndicator($order);
        } catch (LocalizedException $e) {
            //Do nothing
        }
        $input->unscheduledCardOnFileRequestor =
            $this->getUnscheduledCardOnFileRequestor($input->unscheduledCardOnFileSequenceIndicator);
        $request->cardPaymentMethodSpecificInput = $input;

        return $request;
    }

    /**
     * @param OrderInterface $order
     * @return string|null
     * @throws LocalizedException
     */
    private function getUnscheduledCardOnFileSequenceIndicator(OrderInterface $order)
    {
        $payment = $order->getPayment();
        if ($payment === null) {
            throw new LocalizedException(__('No payment available for this order'));
        }
        if ((int) $payment->getAdditionalInformation(Config::PRODUCT_TOKENIZE_KEY) === 1) {
            return self::UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_FIRST;
        }
        if ((int) $payment->getAdditionalInformation(Config::CLIENT_PAYLOAD_IS_PAYMENT_ACCOUNT_ON_FILE) === 1) {
            return self::UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_SUBSEQUENT;
        }
        return null;
    }

    /**
     * @param string|null $unscheduledCardOnFileSequenceIndicator
     * @return string|null
     */
    private function getUnscheduledCardOnFileRequestor($unscheduledCardOnFileSequenceIndicator)
    {
        if ($unscheduledCardOnFileSequenceIndicator === self::UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_FIRST ||
            $unscheduledCardOnFileSequenceIndicator === self::UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_SUBSEQUENT
        ) {
            return self::UNSCHEDULED_CARD_ON_FILE_REQUESTOR_CARDHOLDER_INITIATED;
        }
        return null;
    }
}
