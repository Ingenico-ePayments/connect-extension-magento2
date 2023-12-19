<?php

declare(strict_types=1);

namespace Worldline\Connect\Controller\Adminhtml\Api;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManager;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;

use function array_pop;
use function sprintf;

// phpcs:ignore PSR12.Files.FileHeader.SpacingAfterBlock

class TestConnection extends Action
{
    /** @var Config */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $epaymentsConfig;

    /** @var StoreManager */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $storeManager;

    /** @var JsonFactory */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $jsonFactory;

    /** @var ClientInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $worldlineClient;

    /**
     * @param Context $context
     * @param Config $epaymentsConfig
     * @param StoreManager $storeManager
     * @param JsonFactory $jsonFactory
     * @param ClientInterface $worldlineClient
     */
    public function __construct(
        Context $context,
        Config $epaymentsConfig,
        StoreManager $storeManager,
        JsonFactory $jsonFactory,
        ClientInterface $worldlineClient
    ) {
        parent::__construct($context);
        $this->epaymentsConfig = $epaymentsConfig;
        $this->storeManager = $storeManager;
        $this->jsonFactory = $jsonFactory;
        $this->worldlineClient = $worldlineClient;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function execute()
    {
        try {
            $scopeId = $this->storeManager->getStore()->getId();
            $environment = $this->getRequest()->getParam('environment');
        } catch (NoSuchEntityException $noSuchEntityException) {
            return $this->getFailureResult();
        }

        try {
            $this->worldlineClient->worldlineTestAccount(
                $scopeId,
                $this->worldlineClient->buildFromConfiguration($scopeId, [
                    'api_key' => $this->epaymentsConfig->getApiKey($scopeId, $environment),
                    'api_secret' => $this->epaymentsConfig->getApiSecret($scopeId, $environment),
                    'merchant_id' => $this->epaymentsConfig->getMerchantId($scopeId, $environment),
                    'api_endpoint' => $this->epaymentsConfig->getApiEndpoint($scopeId, $environment),
                ])
            );
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        } catch (\Exception $exception) {
            if ($exception instanceof ResponseException) {
                $errors = $exception->getErrors();
                return $this->getFailureResult(array_pop($errors)->message);
            }
            return $this->getFailureResult();
        }

        return $this->getSuccessResult();
    }

    private function getSuccessResult(): Json
    {
        $result = $this->jsonFactory->create();
        $result->setStatusHeader(200);
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $result->setData(__(
            'Connection to the Worldline Connect platform could successfully be established.'
        ));
        return $result;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    private function getFailureResult($message = null): Json
    {
        $result = $this->jsonFactory->create();
        $result->setStatusHeader(422);
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $responseMessage = __('Could not establish connection to Worldline Connect platform.');
        if ($message !== null) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $responseMessage = sprintf('%s %s %s', $responseMessage, __('Error message:'), $message);
        }
        $result->setData($responseMessage);
        return $result;
    }
}
