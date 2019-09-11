<?php

namespace Ingenico\Connect\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $tableName = $installer->getTable('ingenico_token');
        if ($installer->getConnection()->isTableExists($tableName) != true) {
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary'  => true,
                    ],
                    'Id'
                )
                ->addColumn(
                    'customer_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                    ],
                    'Customer id'
                )
                ->addColumn(
                    'payment_product_id',
                    Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                        'default'  => '',
                    ],
                    'Payment product id'
                )
                ->addColumn(
                    'token',
                    Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                        'default'  => '',
                    ],
                    'Token'
                )
                ->setComment('Payment token table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $installer->getConnection()->createTable($table);

            // add index
            $installer->getConnection()->addIndex(
                $installer->getTable($tableName),
                $installer->getIdxName(
                    $tableName,
                    ['customer_id', 'payment_product_id', 'token'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['customer_id', 'payment_product_id', 'token'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            );
        }

        $installer->endSetup();
    }
}
