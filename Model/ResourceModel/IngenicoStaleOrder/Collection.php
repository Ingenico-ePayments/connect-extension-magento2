<?php

namespace Ingenico\Connect\Model\ResourceModel\IngenicoStaleOrder;

use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\ConfigProvider;

class Collection extends \Magento\Sales\Model\ResourceModel\Order\Collection
{
    /**
     * @var DateTimeFactory
     */
    private $dateFactory;

    /**
     * Collection constructor.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot
     * @param \Magento\Framework\DB\Helper $coreResourceHelper
     * @param DateTimeFactory $dateFactory
     * @param null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\DB\Helper $coreResourceHelper,
        DateTimeFactory $dateFactory,
        $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->dateFactory = $dateFactory;

        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $entitySnapshot,
            $coreResourceHelper,
            $connection,
            $resource
        );
    }

    /**
     * Add filter by cteated_at field, less or equal than $days before
     *
     * @param int $days
     * @param int|null $scopeId
     * @return $this
     */
    public function addCreatedAtFilter($days, $scopeId = null)
    {
        $this->buildFilter($scopeId);

        $date = $this->dateFactory->create();
        $date->sub(new \DateInterval("P${days}D"));

        $this->addFieldToFilter(
            'created_at',
            [
                'lt' => $date->format(DateTime::DATETIME_INTERNAL_FORMAT)
            ]
        );

        return $this;
    }

    /**
     * Build filter skeleton
     *
     * @param int|null $scopeId
     */
    private function buildFilter($scopeId)
    {
        $this->addFieldToFilter('status', Order::STATE_PENDING_PAYMENT);
        $this->addFieldToFilter('method', ConfigProvider::CODE);
        $this->addFieldToFilter('store_id', $scopeId);

        $onCondition = "main_table.entity_id = sales_order_payment.parent_id";
        $this->getSelect()->join('sales_order_payment', $onCondition)->order("main_table.entity_id", 'ASC');
    }
}
