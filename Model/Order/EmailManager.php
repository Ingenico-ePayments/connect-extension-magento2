<?php

namespace Ingenico\Connect\Model\Order;

use Ingenico\Connect\Sdk\Domain\Definitions\KeyValuePair;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\Definitions\DisplayedDataFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Ingenico\Connect\Helper\Data;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\StatusInterface;

class EmailManager
{
    const PAYMENT_UPDATE_EVENT = 'payment_update';
    const EMAIL_TEMPLATE_ID = 'payment_update';
    const PAYMENT_OUTPUT_SHOW_INSTRUCTIONS = 'SHOW_INSTRUCTIONS';

    /**
     * @var ConfigInterface
     */
    private $ePaymentsConfig;

    /**
     * @var EmailProcessor
     */
    private $emailProcessor;

    /**
     * @var ManagerInterface
     */
    private $manager;

    /**
     * @var DisplayedDataFactory
     */
    private $displayedDataFactory;

    /**
     * @var array
     */
    private $statusMapping = [
        'action_needed' => [
            StatusInterface::PENDING_PAYMENT,
        ],
        'payment_successful' => [
            StatusInterface::CAPTURE_REQUESTED,
            StatusInterface::CAPTURED,
            StatusInterface::PAID,
        ],
        'fraud_suspicion' => [
            StatusInterface::PENDING_FRAUD_APPROVAL,
        ],
        'delayed_settlement' => [
            StatusInterface::PENDING_APPROVAL,
        ],
        'slow_3rd_party' => [
            StatusInterface::REDIRECTED,
        ],
    ];

    /**
     * EmailManager constructor.
     *
     * @param ConfigInterface $ePaymentsConfig
     * @param EmailProcessor $emailProcessor
     * @param ManagerInterface $manager
     * @param DisplayedDataFactory $displayedDataFactory
     */
    public function __construct(
        ConfigInterface $ePaymentsConfig,
        EmailProcessor $emailProcessor,
        ManagerInterface $manager,
        DisplayedDataFactory $displayedDataFactory
    ) {
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->emailProcessor = $emailProcessor;
        $this->manager = $manager;
        $this->displayedDataFactory = $displayedDataFactory;
    }

    /**
     * @param OrderInterface $order
     * @param string $ingenicoPaymentStatus
     */
    public function process(OrderInterface $order, $ingenicoPaymentStatus)
    {
        if (!$this->ePaymentsConfig->getUpdateEmailEnabled(
            $this->getMappedStatusValue($ingenicoPaymentStatus),
            $order->getStoreId()
        )) {
            return;
        }

        if ($ingenicoPaymentStatus !== StatusInterface::PENDING_PAYMENT
            && !$order->getPayment()->getAdditionalInformation(Config::PAYMENT_ID_KEY)
        ) {
            return;
        }

        $info = $this->ePaymentsConfig->getPaymentStatusInfo($ingenicoPaymentStatus, $order->getStoreId());
        $instructions = null;
        if ($ingenicoPaymentStatus === StatusInterface::PENDING_PAYMENT
            && $displayedDataJson = $order->getPayment()->getAdditionalInformation(Config::PAYMENT_SHOW_DATA_KEY)) {
            $dispayedData = $this->displayedDataFactory->create();
            $dispayedData->fromJson($displayedDataJson);
            $instructions = $this->formatInstructions($dispayedData->showData);
        }

        if ($ingenicoPaymentStatus === StatusInterface::PENDING_PAYMENT
            && !$instructions
        ) {
            return;
        }

        $emailTemplateVariables = [
            'order' => $order,
            'payment_status' => $ingenicoPaymentStatus,
            'comment' => $info,
            'billing' => $order->getBillingAddress(),
            'instructions' => $instructions,
        ];

        $this->manager->dispatch(self::PAYMENT_UPDATE_EVENT);

        // process email
        $this->emailProcessor->processEmail(
            $order->getStoreId(),
            self::EMAIL_TEMPLATE_ID,
            $order->getCustomerEmail(),
            $this->ePaymentsConfig->getUpdateEmailSender(),
            $emailTemplateVariables
        );
    }

    /**
     * Get mapped status value
     *
     * @param string $ingenicoPaymentStatus
     * @return bool|string
     */
    private function getMappedStatusValue(
        $ingenicoPaymentStatus
    ) {
        $value = false;

        foreach ($this->statusMapping as $key => $statuses) {
            if (in_array($ingenicoPaymentStatus, $statuses)) {
                $value = $key;
                break;
            }
        }

        return $value;
    }

    /**
     * Format instructions
     *
     * @param KeyValuePair[] $instructionsArray
     * @return string
     */
    private function formatInstructions(
        array $instructionsArray
    ) {
        $instructions = '';
        $instructionsCurrency = '';
        $instructionsAmount = '';
        /** @var KeyValuePair $pair */
        foreach ($instructionsArray as $pair) {
            if ($pair->key === 'CURRENCYCODE') {
                $instructionsCurrency = $pair->value;
                continue;
            }
            if ($pair->key === 'AMOUNT') {
                $instructionsAmount = $pair->value;
                continue;
            }

            $pair->key = ucwords(strtolower($pair->key));

            $instructions .= "<tr><td> {$pair->key} </td><td> {$pair->value} </td></tr>";
        }

        if ($instructions !== '' && $instructionsAmount !== '') {
            $instructionsAmount = Data::reformatMagentoAmount($instructionsAmount);
            $amount = __('Amount')->render();
            $instructions .= "<tr><td>{$amount}</td><td> {$instructionsCurrency} {$instructionsAmount} </td></tr>";
        }

        return $instructions;
    }
}
