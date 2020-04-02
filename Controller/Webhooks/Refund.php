<?php

namespace Ingenico\Connect\Controller\Webhooks;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception;
use Ingenico\Connect\Model\Ingenico\Webhook\Handler;
use Ingenico\Connect\Model\Ingenico\Webhook\Event\RefundResolver;

/**
 * Class Refund
 *
 * @package Ingenico\Connect\Controller\Webhooks
 * @deprecated Only one webhook endpoint is offically needed
 * @see \Ingenico\Connect\Controller\Webhooks\Payment::execute
 */
class Refund extends Webhook
{
    /**
     * @var RefundResolver
     */
    private $refundResolver;

    /**
     * @var Handler
     */
    private $webhookHandler;

    /**
     * Refund constructor.
     *
     * @param Context $context
     * @param RefundResolver $refundResolver
     * @param Handler $webhookHandler
     */
    public function __construct(
        Context $context,
        RefundResolver $refundResolver,
        Handler $webhookHandler
    ) {
        parent::__construct($context);
        $this->refundResolver = $refundResolver;
        $this->webhookHandler = $webhookHandler;
    }

    /**
     * Handles refund.* events
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
            $result = $this->webhookHandler->handle($this->refundResolver);
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
