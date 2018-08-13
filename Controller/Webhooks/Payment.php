<?php

namespace Netresearch\Epayments\Controller\Webhooks;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Netresearch\Epayments\Model\Ingenico\Webhooks;
use Netresearch\Epayments\Model\Ingenico\Webhooks\PaymentEventDataResolver;

class Payment extends AbstractWebhook
{
    /** @var PaymentEventDataResolver */
    private $paymentEventDataResolver;

    /** @var Webhooks */
    private $webhooks;

    /**
     * @param Context $context
     * @param PaymentEventDataResolver $paymentEventDataResolver
     * @param Webhooks $webhooks
     */
    public function __construct(
        Context $context,
        PaymentEventDataResolver $paymentEventDataResolver,
        Webhooks $webhooks
    ) {
        parent::__construct($context);
        $this->paymentEventDataResolver = $paymentEventDataResolver;
        $this->webhooks = $webhooks;
    }

    /**
     * Handles payment.* events
     */
    public function execute()
    {
        if ($response = $this->checkVerification()) {
            return $response;
        }
        /** @var string $result */
        $result = $this->webhooks->handle($this->paymentEventDataResolver);

        // build response
        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents($result);

        return $response;
    }
}
