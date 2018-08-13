<?php

namespace Netresearch\Epayments\Model\OrderUpdate;

use Magento\Sales\Model\Order as MagentoOrder;

class HistoryManager implements HistoryManagerInterface
{
    const TYPE_API = 'api';
    const TYPE_WR  = 'wr';

    /**
     * {@inheritdoc}
     */
    public function addHistory(
        MagentoOrder $order,
        array $data,
        $historyType
    ) {
        /** @var string $dbHistory */
        $dbHistory = $this->getHistory($order, $historyType);

        // build data
        $columnData = json_decode($dbHistory, true);
        if (is_array($columnData)) {
            $history = $columnData;
        }
        $history[] = $data;

        // save data
        $this->setHistory($order, $historyType, $history);
    }

    /**
     * Get history from db
     *
     * @param MagentoOrder $order
     * @param $type
     * @return bool|string
     */
    private function getHistory(
        MagentoOrder $order,
        $type
    ) {
        $data = false;
        switch ($type) {
            case self::TYPE_API:
                $data = $order->getOrderUpdateApiHistory();
                break;
            case self::TYPE_WR:
                $data = $order->getOrderUpdateWrHistory();
                break;
        }

        return $data;
    }

    /**
     * Set history db value
     *
     * @param MagentoOrder $order
     * @param $type
     * @param array $data
     */
    private function setHistory(
        MagentoOrder $order,
        $type,
        array $data
    ) {
        $data = json_encode($data);
        switch ($type) {
            case self::TYPE_API:
                $order->setOrderUpdateApiHistory($data);
                break;
            case self::TYPE_WR:
                $order->setOrderUpdateWrHistory($data);
                break;
        }
    }
}
