<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Worldline\Connect\Model\Worldline\MerchantReference;

class SystemPrefix extends Value
{
    /**
     * @var MerchantReference
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $merchantReference;

    /**
     * @var ManagerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $messageManager;

    public function __construct(
        MerchantReference $merchantReference,
        ManagerInterface $messageManager,
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
        AbstractResource $resource = null,
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->merchantReference = $merchantReference;
        $this->messageManager = $messageManager;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function beforeSave()
    {
        parent::beforeSave();

        $merchantReference = $this->_getData('value');
        if (!$this->merchantReference->validateMerchantReference($merchantReference)) {
            $this->messageManager->addWarningMessage(
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                __('The System Identifier Prefix you entered is too long. Please change it to a shorter one.')
            );
        }

        return $this;
    }
}
