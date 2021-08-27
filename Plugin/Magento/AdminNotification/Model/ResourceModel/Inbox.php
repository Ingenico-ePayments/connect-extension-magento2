<?php

declare(strict_types=1);

namespace Ingenico\Connect\Plugin\Magento\AdminNotification\Model\ResourceModel;

class Inbox
{
    /**
     * Temporary fix for GitHub issue #31939
     *
     * @param \Magento\AdminNotification\Model\ResourceModel\Inbox $subject
     * @param callable $proceed
     * @param \Magento\AdminNotification\Model\Inbox $object
     * @param array $data
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @link https://github.com/magento/magento2/issues/31939
     * @link https://github.com/magento/magento2/pull/31942
     */
    public function aroundParse(
        \Magento\AdminNotification\Model\ResourceModel\Inbox $subject,
        callable $proceed,
        \Magento\AdminNotification\Model\Inbox $object,
        array $data
    ) {
        $connection = $subject->getConnection();

        foreach ($data as $item) {
            $select = $connection->select()->from($subject->getMainTable())->where('title = ?', $item['title']);

            if (empty($item['url'])) {
                $select->where('url IS NULL');
                unset($item['url']);
            } else {
                $select->where('url = ?', $item['url']);
            }

            if (isset($item['internal'])) {
                if ($item['internal']) {
                    $row = false;
                } else {
                    $row = $connection->fetchRow($select);
                }
                unset($item['internal']);
            } else {
                $row = $connection->fetchRow($select);
            }

            if (!$row) {
                $connection->insert($subject->getMainTable(), $item);
            }
        }
    }
}
