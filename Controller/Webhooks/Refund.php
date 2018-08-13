<?php

namespace Netresearch\Epayments\Controller\Webhooks;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Netresearch\Epayments\Model\Ingenico\Webhooks;
use Netresearch\Epayments\Model\Ingenico\Webhooks\RefundEventDataResolver;

class Refund extends AbstractWebhook
{
    /** @var RefundEventDataResolver */
    private $refundEventDataResolver;

    /** @var Webhooks */
    private $webhooks;

    /**
     * @param Context $context
     * @param RefundEventDataResolver $refundEventDataResolver
     * @param Webhooks $webhooks
     */
    public function __construct(
        Context $context,
        RefundEventDataResolver $refundEventDataResolver,
        Webhooks $webhooks
    ) {
        parent::__construct($context);
        $this->refundEventDataResolver = $refundEventDataResolver;
        $this->webhooks = $webhooks;
    }

    /**
     * Handles refund.* events
     */
    public function execute()
    {
        if ($response = $this->checkVerification()) {
            return $response;
        }
        /** @var string $result */
        $result = $this->webhooks->handle($this->refundEventDataResolver);

        // build response
        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents($result);

        return $response;
    }
}
