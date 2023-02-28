<?php

declare(strict_types=1);

namespace Worldline\Connect\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Psr\Log\LoggerInterface;
use Worldline\Connect\Api\Data\EventInterface;
use Zend_Db_Exception;

use function version_compare;

// phpcs:ignore PSR12.Files.FileHeader.SpacingAfterBlock

/**
 * Class UpgradeSchema
 *
 * @package Worldline\Connect\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
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

        if (version_compare($context->getVersion(), '2.1.2', '<')) {
            $this->updateWebhookEventCreatedAtAttribute($setup);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    private function addWxUpdateColumns(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('sales_order');
        if ($setup->getConnection()->isTableExists($tableName)) {
            $columns = [
                'order_update_wr_status' => [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Order Update Wr Status',
                ],
                'order_update_wr_first_time' => [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Order Update Wr First Time',
                ],
                'order_update_wr_history' => [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Order Update Wr History',
                ],
                'order_update_api_last_attempt_time' => [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Order Update Api Last Attempt Time',
                ],
                'order_update_api_history' => [
                    'type' => Table::TYPE_TEXT,
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
     * @throws Zend_Db_Exception
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
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
                ['resource_id' => 'Worldline_Connect::epayments_config'],
                'resource_id = "Netresearch_Epayments::epayments_config"'
            );
            $setup->getConnection()->update(
                $tableName,
                ['resource_id' => 'Worldline_Connect::download_logfile'],
                'resource_id = "Netresearch_Epayments::download_logfile"'
            );
        }
    }

    private function updateWebhookEventCreatedAtAttribute(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('epayments_webhook_event');
        if ($setup->getConnection()->isTableExists($tableName)) {
            // We need to perform a native query, since Magento's MySQL adapter does not support
            // setting a length for a timestamp prior before v5.6.4 :
            $version = $setup->getConnection()->fetchOne('SELECT VERSION()');
            if (version_compare($version, '5.6.4', '>=')) {
                $setup->getConnection()->query(
                    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                    sprintf(
                        'ALTER TABLE %1$s MODIFY COLUMN %2$s TIMESTAMP(4) NULL COMMENT \'%3$s\';',
                        $tableName,
                        EventInterface::CREATED_TIMESTAMP,
                        'Creation date of event on platform'
                    )
                );
            } else {
                // phpcs:ignore Generic.Files.LineLength.TooLong
                $this->logger->warning(
                    'MySQL version does not support fractional seconds. Race conditions in webhooks might occur'
                );
            }
        }
    }
}
