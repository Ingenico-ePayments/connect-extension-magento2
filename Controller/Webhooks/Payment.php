<?php

declare(strict_types=1);

namespace Ingenico\Connect\Controller\Webhooks;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Webapi\Exception as WebApiException;
use Ingenico\Connect\Model\Ingenico\Webhook\Handler;
use Ingenico\Connect\Model\Ingenico\Webhook\Event\PaymentResolver;
use Psr\Log\LoggerInterface;
use RuntimeException;

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

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        PaymentResolver $paymentResolver,
        Handler $webhookHandler
    ) {
        parent::__construct($context, $logger);

        $this->paymentResolver = $paymentResolver;
        $this->webhookHandler = $webhookHandler;
    }

    /**
     * Handles payment.* events
     *
     * @return false|ResponseInterface|ResultInterface
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
        } catch (RuntimeException $exception) {
            $this->logException($exception);
            $response->setHttpResponseCode(WebApiException::HTTP_INTERNAL_ERROR);
        } catch (Exception $exception) {
            $this->logException($exception);
            $response->setContents($exception->getMessage());
        }

        return $response;
    }
}
