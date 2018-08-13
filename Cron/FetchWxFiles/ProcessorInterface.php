<?php

namespace Netresearch\Epayments\Cron\FetchWxFiles;

interface ProcessorInterface
{
    /**
     * Apply .wr file to order
     *
     * @param $storeId
     * @param $date
     */
    public function process($storeId, $date = 'yesterday');
}
