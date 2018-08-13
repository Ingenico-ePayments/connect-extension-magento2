<?php
namespace Netresearch\Epayments\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class ApiKey extends Value
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        EncryptorInterface $encryptor,
        array $data = []
    ) {
        $this->encryptor = $encryptor;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return Value
     */
    protected function _afterLoad()
    {
        $value = $this->encryptor->decrypt($this->getValue());
        $this->setValue($value);

        return $this;
    }

    /**
     * @return Value
     */
    public function beforeSave()
    {
        $value = $this->getValue();

        if (!empty($value)) {
            $value = $this->encryptor->encrypt($value);
            $this->setValue($value);
        }
        return parent::beforeSave();
    }

    /**
     * @return bool
     */
    public function isValueChanged()
    {
        return $this->encryptor->decrypt($this->getOldValue()) !== $this->getValue();
    }

    /**
     * @return bool
     */
    public function hasDataChanges()
    {
        return $this->isValueChanged();
    }
}
