<?php

declare(strict_types=1);

namespace Ingenico\Connect\Controller\Adminhtml\Api;

use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManager;
use function array_pop;
use function sprintf;

class TestConnection extends Action
{
    /** @var Config */
    private $epaymentsConfig;

    /** @var StoreManager */
    private $storeManager;

    /** @var JsonFactory */
    private $jsonFactory;

    /** @var ClientInterface */
    protected $ingenicoClient;

    /**
     * @param Context $context
     * @param Config $epaymentsConfig
     * @param StoreManager $storeManager
     * @param JsonFactory $jsonFactory
     * @param ClientInterface $ingenicoClient
     */
    public function __construct(
        Context $context,
        Config $epaymentsConfig,
        StoreManager $storeManager,
        JsonFactory $jsonFactory,
        ClientInterface $ingenicoClient
    ) {
        parent::__construct($context);
        $this->epaymentsConfig = $epaymentsConfig;
        $this->storeManager = $storeManager;
        $this->jsonFactory = $jsonFactory;
        $this->ingenicoClient = $ingenicoClient;
    }

    public function execute()
    {
        try {
            $scopeId = $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $noSuchEntityException) {
            return $this->getFailureResult();
        }

        $configData = [
            'api_key' => $this->epaymentsConfig->getApiKey($scopeId),
            'api_secret' => $this->epaymentsConfig->getApiSecret($scopeId),
            'merchant_id' => $this->epaymentsConfig->getMerchantId($scopeId),
            'api_endpoint' => $this->epaymentsConfig->getApiEndpoint($scopeId),
        ];

        try {
            $this->ingenicoClient->ingenicoTestAccount($scopeId, $configData);
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
        $result->setData(__(
            'Connection to the Ingenico Connect platform could successfully be established.'
        ));
        return $result;
    }

    private function getFailureResult($message = null): Json
    {
        $result = $this->jsonFactory->create();
        $result->setStatusHeader(422);
        $responseMessage = __('Could not establish connection to Ingenico Connect platform.');
        if ($message !== null) {
            $responseMessage = sprintf('%s %s %s', $responseMessage, __('Error message:'), $message);
        }
        $result->setData($responseMessage);
        return $result;
    }
}
