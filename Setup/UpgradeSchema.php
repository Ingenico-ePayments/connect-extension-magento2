<?php

namespace Netresearch\Epayments\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $tableName = $setup->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $columns = [
                    'order_update_wr_status'             => [
                        'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length'   => 255,
                        'nullable' => true,
                        'comment'  => 'Order Update Wr Status',
                    ],
                    'order_update_wr_first_time'         => [
                        'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length'   => 255,
                        'nullable' => true,
                        'comment'  => 'Order Update Wr First Time',
                    ],
                    'order_update_wr_history'            => [
                        'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment'  => 'Order Update Wr History',
                    ],
                    'order_update_api_last_attempt_time' => [
                        'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length'   => 255,
                        'nullable' => true,
                        'comment'  => 'Order Update Api Last Attempt Time',
                    ],
                    'order_update_api_history'           => [
                        'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment'  => 'Order Update Api History',
                    ],
                ];
                $connection = $setup->getConnection();
                foreach ($columns as $name => $definition) {
                    $connection->addColumn($tableName, $name, $definition);
                }
            }
        }

        $setup->endSetup();
    }
}
