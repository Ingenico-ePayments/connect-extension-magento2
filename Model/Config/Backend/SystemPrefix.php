<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Config\Backend;

use Ingenico\Connect\Model\Ingenico\MerchantReference;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class SystemPrefix extends Value
{
    /**
     * @var MerchantReference
     */
    private $merchantReference;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    public function __construct(
        MerchantReference $merchantReference,
        ManagerInterface $messageManager,
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->merchantReference = $merchantReference;
        $this->messageManager = $messageManager;
    }

    public function beforeSave()
    {
        parent::beforeSave();

        $merchantReference = $this->_getData('value');
        if (!$this->merchantReference->validateMerchantReference($merchantReference)) {
            $this->messageManager->addWarningMessage(
                __('The System Identifier Prefix you entered is too long. Please change it to a shorter one.')
            );
        }

        return $this;
    }
}
