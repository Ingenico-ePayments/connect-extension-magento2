<?php

namespace Ingenico\Connect\Controller\Webhooks;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Ingenico\Connect\Controller\CsrfAware\Action;
use Psr\Log\LoggerInterface;

/**
 * Abstract webhook class encapsulating general request validation functionality for webhooks
 */
abstract class Webhook extends Action
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
    }

    /**
     * Checks the headers of the request for a special endpoint verification
     *
     * @return ResultInterface|false
     */
    protected function checkVerification()
    {
        $verificationString = $this->getRequest()->getHeader('X-GCS-Webhooks-Endpoint-Verification');
        if ($verificationString) {
            $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $response->setHeader('Content-type', 'text/plain');
            $response->setContents($verificationString);

            return $response;
        }

        return false;
    }

    /**
     * Explicitly set the response code to 500 to allow the Connect platform to retry the webhook request, with possibly
     * correct headers next time
     *
     * @param RequestInterface $request
     * @return Raw|ResultInterface
     * @see \Ingenico\Connect\Controller\Webhooks\Webhook::proxyValidateForCsrf
     */
    protected function getCsrfExceptionResponse(RequestInterface $request)
    {
        /** @var Raw $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setHttpResponseCode(500);
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents(__('Action is not allowed.'));

        return $response;
    }

    /**
     * Check request validity only by relevant headers actually existing. The expensive check of unhashing the request
     * body will be done later down the line
     *
     * @param RequestInterface $request
     * @return bool
     */
    protected function proxyValidateForCsrf(RequestInterface $request)
    {
        /** @var string $securitySignature */
        $securitySignature = $this->getRequest()->getHeader('X-GCS-Signature');
        /** @var string $securityKey */
        $securityKey = $this->getRequest()->getHeader('X-GCS-KeyId');
        /** @var string $verificationString */
        $verificationString = $this->getRequest()->getHeader('X-GCS-Webhooks-Endpoint-Verification');

        return ($securitySignature && $securityKey) || $verificationString;
    }

    protected function logException(Exception $exception)
    {
        $this->logger->warning(
            sprintf(
                'Exception occurred when attempting to handle webhook: %1$s',
                $exception->getMessage()
            )
        );
        $this->logger->debug(
            'Webhook details',
            [
                'headers' => $this->getHeaders(),
                'body' => $this->getBody(),
            ]
        );
    }

    private function getHeaders(): array
    {
        $request = $this->getRequest();

        if (!$request instanceof Http) {
            return [];
        }

        $headers = [];
        foreach ($request->getHeaders() as $header) {
            $headers[$header->getFieldName()] = $header->getFieldValue();
        }
        return $headers;
    }

    private function getBody(): string
    {
        $request = $this->getRequest();

        if (!$request instanceof Http) {
            return '';
        }

        return $request->getContent();
    }
}
