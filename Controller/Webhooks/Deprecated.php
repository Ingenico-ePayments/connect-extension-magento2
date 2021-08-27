<?php

declare(strict_types=1);

namespace Ingenico\Connect\Controller\Webhooks;

// phpcs:disable Generic.Files.LineLength.TooLong

use Ingenico\Connect\Block\Adminhtml\System\Config\Field\WebhookEndpoint;
use Ingenico\Connect\Model\Ingenico\Webhook\Handler;
use Ingenico\Connect\Model\Ingenico\Webhook\Unmarshaller;
use Magento\AdminNotification\Model\Inbox;
use Magento\Framework\App\Action\Context;
use Psr\Log\LoggerInterface;

class Deprecated extends Index
{
    /**
     * @var Inbox
     */
    private $inbox;

    /**
     * @var WebhookEndpoint
     */
    private $webhookEndpoint;

    public function __construct(
        Context $context,
        Unmarshaller $unmarshaller,
        Handler $handler,
        LoggerInterface $logger,
        Inbox $inbox,
        WebhookEndpoint $webhookEndpoint
    ) {
        parent::__construct($context, $unmarshaller, $handler, $logger);

        $this->inbox = $inbox;
        $this->webhookEndpoint = $webhookEndpoint;
    }

    protected function addDeprecationNotice(): void
    {
        $this->inbox->addMajor(
            __('You need to update your webhook endpoints in the Ingenico Configuration Center.'),
            __(
                'Your webhook endpoints in the Ingenico Configuration Center are configured on the two separate payment- and refund webhooks endpoints. These endpoints might become deprecated in the near future. That\'s why it\'s important to update this. Please remove the two separate endpoints and replace them with: %1. Make sure to check all checkboxes for "payment" and "refund". If you have any further questions, please contact Merchant Support.',
                $this->webhookEndpoint->getWebhookUrl()
            ),
            '',
            false
        );
    }
}
