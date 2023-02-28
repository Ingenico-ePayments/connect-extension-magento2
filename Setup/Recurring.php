<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Setup;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\FullModuleList;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class Recurring implements InstallSchemaInterface
{
    /**
     * @var FullModuleList
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $fullModuleList;

    public function __construct(FullModuleList $fullModuleList)
    {
        $this->fullModuleList = $fullModuleList;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (in_array('Netresearch_Epayments', $this->fullModuleList->getNames())) {
            throw new LocalizedException(
            // phpcs:ignore Generic.Files.LineLength.TooLong, SlevomatCodingStandard.Files.LineLength.LineTooLong, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                __('The Worldline_Connect module cannot be installed because the Netresearch_Epayments is found. Please read the upgrade instructions in doc/UPGRADE.md')
            );
        }
    }
}
