<?php

namespace Ingenico\Connect\Cron\FetchWxFiles;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;

interface StatusUpdateResolverInterface
{
    /**
     * @param AbstractOrderStatus[] $statusList
     */
    public function resolveBatch($statusList);
}
