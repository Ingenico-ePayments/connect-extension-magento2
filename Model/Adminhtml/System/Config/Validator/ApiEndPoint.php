<?php

namespace Ingenico\Connect\Model\Adminhtml\System\Config\Validator;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\Action\TestAccountAction;

class ApiEndPoint extends Value
{
    private static $errorMessage =
        'Could not establish connection to Ingenico Connect platform. Please check your account settings.';
    private static $successMessage = 'Connection to the Ingenico Connect platform could successfully be established.';

    private $keys = [
        'api_key',
        'api_secret',
        'merchant_id',
        'api_endpoint',
    ];

    /**
     * @var Config
     */
    private $epaymentsConfig;

    /**
     * @var TestAccountAction
     */
    private $testAccountAction;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * ApiEndPoint constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param Config $epaymentsConfig
     * @param TestAccountAction $testAccountAction
     * @param ManagerInterface $messageManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        Config $epaymentsConfig,
        TestAccountAction $testAccountAction,
        ManagerInterface $messageManager,
        array $data = []
    ) {
        $this->epaymentsConfig = $epaymentsConfig;
        $this->testAccountAction = $testAccountAction;
        $this->messageManager = $messageManager;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function afterSave()
    {
        parent::afterSave();

        $fieldsetData = $this->_getData('fieldset_data');

        $configData = [
            'api_key' => $this->epaymentsConfig->getApiKey($this->getScopeId()),
            'api_secret' => $this->epaymentsConfig->getApiSecret($this->getScopeId()),
            'merchant_id' => $this->epaymentsConfig->getMerchantId($this->getScopeId()),
            'api_endpoint' => $this->epaymentsConfig->getApiEndpoint($this->getScopeId()),
        ];

        $data = array_intersect_key($fieldsetData, array_flip($this->keys));

        if (isset($data['api_key']) && $this->isNotPasswordInputChanged($data['api_key'])) {
            $data['api_key'] = $configData['api_key'];
        }
        if (isset($data['api_secret']) && $this->isNotPasswordInputChanged($data['api_secret'])) {
            $data['api_secret'] = $configData['api_secret'];
        }

        $equals = $this->compareValues($configData, $data);
        $filled = $this->isArrayComplete($data);

        if (!$equals) {
            if (!$filled) {
                $this->messageManager->addWarningMessage(__(self::$errorMessage));
            } else {
                $this->runTest($data);
            }
        }

        return $this;
    }

    /**
     * @param string $data
     * @return bool
     */
    public function isNotPasswordInputChanged($data)
    {
        return (bool) preg_match('/^\*+$/', $data);
    }

    /**
     * @param string[] $configData
     * @param string[] $data
     * @return bool
     */
    public function compareValues($configData = [], $data = [])
    {
        $result = false;
        if ($data === array_intersect($data, $configData) && $configData === array_intersect($configData, $data)) {
            $result = true;
        }

        return $result;
    }

    /**
     * @param array $array
     * @return bool
     */
    public function isArrayComplete($array = [])
    {
        $result = true;
        foreach ($array as $value) {
            if ($value === '') {
                $result = false;
                break;
            }
        }
        return $result;
    }

    /**
     * @param array $data
     */
    public function runTest($data = [])
    {
        $testResponse = $this->testAccountAction->process($this->getScopeId(), $data);

        if ($testResponse === TestAccountAction::STATE_OK) {
            $this->messageManager->addSuccessMessage(
                __(self::$successMessage)
            );
        } else {
            $this->messageManager->addWarningMessage(
                __(self::$errorMessage)
            );
        }
    }
}
