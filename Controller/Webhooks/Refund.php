<?php

namespace Netresearch\Epayments\Controller\Webhooks;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception;
use Netresearch\Epayments\Model\Ingenico\Webhooks;
use Netresearch\Epayments\Model\Ingenico\Webhooks\RefundEventDataResolver;

/**
 * Class Refund
 *
 * @package Netresearch\Epayments\Controller\Webhooks
 * @deprecated Only one webhook endpoint is offically needed
 * @see \Netresearch\Epayments\Controller\Webhooks\Payment::execute
 */
class Refund extends Webhook
{
    /**
     * @var RefundEventDataResolver
     */
    private $refundEventDataResolver;

    /**
     * @var Webhooks
     */
    private $webhooks;

    /**
     * Refund constructor.
     *
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
            $result = $this->webhooks->handle($this->refundEventDataResolver);
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
