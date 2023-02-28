<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Config\Backend;

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
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $encryptor;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
        AbstractResource $resource = null,
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
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
    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
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

        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
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
