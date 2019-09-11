<?php

namespace Ingenico\Connect\Model\Order;

use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Ingenico\Connect\Model\ConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Event\Manager;

class EmailManagerFraud
{
    const FRAUD_EMAIL_EVENT = 'ingenico_fraud';

    /** @var ConfigInterface */
    private $ePaymentsConfig;

    /** @var EmailProcessor */
    private $emailProcessor;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var Manager */
    private $manager;

    /**
     * @param ConfigInterface $ePaymentsConfig
     * @param EmailProcessor $emailProcessor
     * @param UrlInterface $urlBuilder
     * @param ManagerInterface $manager
     */
    public function __construct(
        ConfigInterface $ePaymentsConfig,
        EmailProcessor $emailProcessor,
        UrlInterface $urlBuilder,
        ManagerInterface $manager
    ) {
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->emailProcessor = $emailProcessor;
        $this->urlBuilder = $urlBuilder;
        $this->manager = $manager;
    }

    /**
     * {@inheritDoc}
     */
    public function process(OrderInterface $order)
    {
        $orderLink = $this->urlBuilder->getUrl("sales/order/view/", ['order_id' => $order->getEntityId()]);
        $emailTemplateVariables = [
            'order'      => $order,
            'order_link' => $orderLink,
        ];

        $this->manager->dispatch(self::FRAUD_EMAIL_EVENT);

        // process email
        $storeId = $order->getStoreId();
        $this->emailProcessor->processEmail(
            $storeId,
            $this->ePaymentsConfig->getFraudEmailTemplate($storeId),
            $this->ePaymentsConfig->getFraudManagerEmail($storeId),
            $this->ePaymentsConfig->getFraudEmailSender($storeId),
            $emailTemplateVariables
        );
    }
}
