<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Controller\Webhooks;

use Exception;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Webapi\Exception as WebApiException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Worldline\Connect\Controller\CsrfAware\Action;
use Worldline\Connect\Model\Worldline\Webhook\Handler;
use Worldline\Connect\Model\Worldline\Webhook\Unmarshaller;

/**
 * Webhook class encapsulating general request validation functionality for webhooks
 */
class Index extends Action
{
    /**
     * @var Unmarshaller
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $unmarshaller;

    /**
     * @var Handler
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $handler;

    /**
     * @var LoggerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $logger;

    public function __construct(
        Context $context,
        Unmarshaller $unmarshaller,
        Handler $handler,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->unmarshaller = $unmarshaller;
        $this->handler = $handler;
        $this->logger = $logger;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function execute()
    {
        if ($response = $this->checkVerification()) {
            return $response;
        }

        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setHeader('Content-type', 'text/plain');

        try {
            $event = $this->getWebhookEvent();
            if (!$this->checkEndpointTest($event)) {
                $this->handler->handle($event);
            }
            $response->setContents($this->getSecuritySignature());
        } catch (RuntimeException $exception) {
            $this->logException($exception);
            $response->setHttpResponseCode(WebApiException::HTTP_INTERNAL_ERROR);
        } catch (Exception $exception) {
            $this->logException($exception);
            $response->setContents($exception->getMessage());
        }

        return $response;
    }

    private function getWebhookEvent(): WebhooksEvent
    {
        $securityKey = (string) $this->getRequest()->getHeader('X-GCS-KeyId');
        $event = $this->unmarshaller->unmarshal(
            $this->getRequest()->getContent(),
            [
                'X-GCS-Signature' => $this->getSecuritySignature(),
                'X-GCS-KeyId' => $securityKey,
            ]
        );

        return $event;
    }

    /**
     * Detects Worldline Webhook test request.
     * When a request is an endpoint test, it should not be processed.
     *
     * @param WebhooksEvent $event
     * @return bool
     */
    private function checkEndpointTest(WebhooksEvent $event): bool
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return strpos((string) $event->id, 'TEST') === 0;
    }

    private function getSecuritySignature(): string
    {
        return (string) $this->getRequest()->getHeader('X-GCS-Signature');
    }

    /**
     * Checks the headers of the request for a special endpoint verification
     */
    private function checkVerification(): ?ResultInterface
    {
        $verificationString = $this->getRequest()->getHeader('X-GCS-Webhooks-Endpoint-Verification');
        if ($verificationString) {
            $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $response->setHeader('Content-type', 'text/plain');
            $response->setContents($verificationString);

            return $response;
        }

        return null;
    }

    /**
     * Explicitly set the response code to 500 to allow the Connect platform to retry the webhook request, with possibly
     * correct headers next time
     *
     * @param RequestInterface $request
     * @return Raw|ResultInterface
     * @see \Worldline\Connect\Controller\Webhooks\Index::proxyValidateForCsrf
     */
    protected function getCsrfExceptionResponse(RequestInterface $request)
    {
        /** @var Raw $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setHttpResponseCode(500);
        $response->setHeader('Content-type', 'text/plain');
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
        return true;
        /** @var string $securitySignature */
        $securitySignature = $this->getRequest()->getHeader('X-GCS-Signature');
        /** @var string $securityKey */
        $securityKey = $this->getRequest()->getHeader('X-GCS-KeyId');
        /** @var string $verificationString */
        $verificationString = $this->getRequest()->getHeader('X-GCS-Webhooks-Endpoint-Verification');

        return ($securitySignature && $securityKey) || $verificationString;
    }

    private function logException(Exception $exception)
    {
        $this->logger->warning(
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
