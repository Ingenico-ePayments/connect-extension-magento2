<?php

namespace Ingenico\Connect\Setup;

use Ingenico\Connect\Api\Data\EventInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
 *
 * @package Ingenico\Connect\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $this->addWxUpdateColumns($setup);
        }
        if (version_compare($context->getVersion(), '1.5.0') < 0) {
            $this->addWebhookEventTable($setup);
        }
        if (version_compare($context->getVersion(), '1.5.1') < 0) {
            $this->updateWebhookEventTable($setup);
        }
        if (version_compare($context->getVersion(), '2.0.0') < 0) {
            $this->updateAclRolesResourceId($setup);
        }
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addWxUpdateColumns(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('sales_order');
        if ($setup->getConnection()->isTableExists($tableName) == true) {
            $columns = [
                'order_update_wr_status' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Order Update Wr Status',
                ],
                'order_update_wr_first_time' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Order Update Wr First Time',
                ],
                'order_update_wr_history' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Order Update Wr History',
                ],
                'order_update_api_last_attempt_time' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Order Update Api Last Attempt Time',
                ],
                'order_update_api_history' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Order Update Api History',
                ],
            ];
            $connection = $setup->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($tableName, $name, $definition);
            }
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function addWebhookEventTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('epayments_webhook_event');
        if ($setup->getConnection()->isTableExists($tableName) === false) {
            $table = $setup->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    EventInterface::ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true,
                    ],
                    'Id'
                )
                ->addColumn(
                    EventInterface::EVENT_ID,
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => false,
                    ],
                    'Webhook event id'
                )
                ->addColumn(
                    EventInterface::ORDER_INCREMENT_ID,
                    Table::TYPE_TEXT,
                    50,
                    [
                        'nullable' => false,
                    ],
                    'merchant reference / order increment id'
                )
                ->addColumn(
                    EventInterface::PAYLOAD,
                    Table::TYPE_TEXT,
                    null,
                    [],
                    'Original event data payload'
                )
                ->addColumn(
                    EventInterface::STATUS,
                    Table::TYPE_INTEGER,
                    1,
                    [
                        'unsigned' => true,
                        'default' => 0,
                    ],
                    'Processing status of the webhook event'
                )
                ->addIndex(
                    $setup->getIdxName($tableName, ['event_id', 'order_increment_id']),
                    ['event_id', 'order_increment_id']
                );
            $setup->getConnection()->createTable($table);
        }
    }

    private function updateWebhookEventTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('epayments_webhook_event');
        if ($setup->getConnection()->isTableExists($tableName)) {
            $setup->getConnection()->addColumn(
                $tableName,
                EventInterface::CREATED_TIMESTAMP,
                [
                    'TYPE' => Table::TYPE_TIMESTAMP,
                    'COMMENT' => 'Creation date of event on platform',
                ]
            );
        }
    }

    private function updateAclRolesResourceId(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('authorization_rule');
        if ($setup->getConnection()->isTableExists($tableName)) {
            $setup->getConnection()->update(
                $tableName,
                ['resource_id' => 'Ingenico_Connect::epayments_config'],
                'resource_id = "Netresearch_Epayments::epayments_config"'
            );
            $setup->getConnection()->update(
                $tableName,
                ['resource_id' => 'Ingenico_Connect::download_logfile'],
                'resource_id = "Netresearch_Epayments::download_logfile"'
            );
        }
    }
}
