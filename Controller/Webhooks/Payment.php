<?php

namespace Ingenico\Connect\Controller\Webhooks;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception;
use Ingenico\Connect\Model\Ingenico\Webhook\Handler;
use Ingenico\Connect\Model\Ingenico\Webhook\Event\PaymentResolver;

/**
 * Class Payment
 *
 * @package Ingenico\Connect\Controller\Webhooks
 */
class Payment extends Webhook
{
    /**
     * @var PaymentResolver
     */
    private $paymentResolver;

    /**
     * @var Handler
     */
    private $webhookHandler;

    /**
     * Payment constructor.
     *
     * @param Context $context
     * @param PaymentResolver $paymentResolver
     * @param Handler $webhookHandler
     */
    public function __construct(
        Context $context,
        PaymentResolver $paymentResolver,
        Handler $webhookHandler
    ) {
        parent::__construct($context);
        $this->paymentResolver = $paymentResolver;
        $this->webhookHandler = $webhookHandler;
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
            $result = $this->webhookHandler->handle($this->paymentResolver);
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
