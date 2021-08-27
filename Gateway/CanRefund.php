<?php

namespace Ingenico\Connect\Gateway;

use Magento\Framework\App\RequestInterface;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;

class CanRefund implements ValueHandlerInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        RequestInterface $request
    ) {
        $this->request = $request;
    }

    public function handle(array $subject, $storeId = null)
    {
        // Since the introduction of the refund queue, a refund can always be done
        // @todo: undo this

        if ($creditMemoId = (int) $this->request->getParam('creditmemo_id')) {
            // If we're on the credit memo page, hide the "refund"-button
            return false;
        }

        return true;
    }
}
