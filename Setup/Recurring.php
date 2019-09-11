<?php

namespace Ingenico\Connect\Setup;

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
    private $fullModuleList;

    public function __construct(FullModuleList $fullModuleList)
    {
        $this->fullModuleList = $fullModuleList;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (in_array('Netresearch_Epayments', $this->fullModuleList->getNames())) {
            throw new LocalizedException(
            // phpcs:ignore Generic.Files.LineLength.TooLong
                __('The Ingenico_Connect module cannot be installed because the Netresearch_Epayments is found. Please read the upgrade instructions in doc/UPGRADE.md')
            );
        }
    }
}
