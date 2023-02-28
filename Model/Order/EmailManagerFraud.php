<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Worldline\Connect\Model\ConfigInterface;

class EmailManagerFraud
{
    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $ePaymentsConfig;

    /**
     * @var EmailProcessor
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $emailProcessor;

    /**
     * @var UrlInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $urlBuilder;

    /**
     * @var NotifierInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $notifier;

    /**
     * @param ConfigInterface $ePaymentsConfig
     * @param EmailProcessor $emailProcessor
     * @param UrlInterface $urlBuilder
     * @param NotifierInterface $notifier
     */
    public function __construct(
        ConfigInterface $ePaymentsConfig,
        EmailProcessor $emailProcessor,
        UrlInterface $urlBuilder,
        NotifierInterface $notifier
    ) {
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->emailProcessor = $emailProcessor;
        $this->urlBuilder = $urlBuilder;
        $this->notifier = $notifier;
    }

    /**
     * @param OrderInterface $order
     * @throws LocalizedException
     * @throws MailException
     */
    public function process(OrderInterface $order)
    {
        $this->notify($order);
    }

    /**
     * @param OrderInterface $order
     * @throws LocalizedException
     * @throws MailException
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    private function notify(OrderInterface $order)
    {
        $storeId = $order->getStoreId();
        if ($this->ePaymentsConfig->getFraudManagerEmail($storeId)) {
            // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.NotRequired
            $adminOrderUrl = $this->urlBuilder->getUrl("sales/order/view/", ['order_id' => $order->getEntityId()]);
            $emailTemplateVariables = [
                'order'      => $order,
                'order_link' => $adminOrderUrl,
            ];

            $this->emailProcessor->processEmail(
                $storeId,
                $this->ePaymentsConfig->getFraudEmailTemplate($storeId),
                $this->ePaymentsConfig->getFraudManagerEmail($storeId),
                $this->ePaymentsConfig->getFraudEmailSender($storeId),
                $emailTemplateVariables
            );

            return;
        }

        $this->notifier->addMinor(
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            __('Order suspected for fraud'),
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            __(
                'Order #%1, placed on %2 by %3 (%4) suspected for fraud',
                $order->getIncrementId(),
                $order->getCreatedAt(),
                $order->getCustomerName(),
                $order->getCustomerEmail()
            )
        );
    }
}
