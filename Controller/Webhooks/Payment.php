<?php

namespace Ingenico\Connect\Controller\Webhooks;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception;
use Ingenico\Connect\Model\Ingenico\Webhooks;
use Ingenico\Connect\Model\Ingenico\Webhooks\PaymentEventDataResolver;

/**
 * Class Payment
 *
 * @package Ingenico\Connect\Controller\Webhooks
 */
class Payment extends Webhook
{
    /**
     * @var PaymentEventDataResolver
     */
    private $paymentEventDataResolver;

    /**
     * @var Webhooks
     */
    private $webhooks;

    /**
     * Payment constructor.
     *
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
     *
     * @return false|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($response = $this->checkVerification()) {
            return $response;
        }

        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setHeader('Content-type', 'text/plain');

        try {
            /** @var string $result */
            $result = $this->webhooks->handle($this->paymentEventDataResolver);
            $response->setContents($result);
        } catch (\RuntimeException $exception) {
            // on invalid signature or version mismatch the event could not be unwrapped
            $response->setHttpResponseCode(Exception::HTTP_INTERNAL_ERROR);
        } catch (\Exception $exception) {
            $response->setContents($exception->getMessage());
        }

        return $response;
    }
}
