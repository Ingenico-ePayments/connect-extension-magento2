<?php

declare(strict_types=1);

namespace Ingenico\Connect\Plugin\Magento\Sales\Block\Adminhtml\Order\Invoice;

use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Transaction\TransactionManager;
use Ingenico\Connect\Plugin\Magento\Sales\Block\Adminhtml\Order\AbstractOrder;
use Magento\Sales\Block\Adminhtml\Order\Invoice\View as BaseView;

class View extends AbstractOrder
{
    /**
     * @var TransactionManager
     */
    private $transactionManager;

    public function __construct(
        TransactionManager $transactionManager,
        ConfigInterface $config
    ) {
        parent::__construct($config);
        $this->transactionManager = $transactionManager;
    }

    /**
     * We mis-use the getPrintUrl()-method for this, since the original
     * class does everything in the constructor and has no reasonable
     * options to manipulate the credit memo-button in a different way
     *
     * @param BaseView $subject
     * @return null
     */
    public function beforeGetPrintUrl(BaseView $subject)
    {
        if (!$this->allowOfflineRefund($subject->getInvoice()->getOrder())) {
            // Check if transaction is refundable, because in that case
            // the credit-memo button should be shown:
            $transaction = $this->transactionManager->retrieveTransaction($subject->getInvoice()->getTransactionId());
            if ($transaction) {
                /** @var \Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment $responseObject */
                $responseObject = $this->transactionManager->getResponseDataFromTransaction($transaction);
                if (isset($responseObject->statusOutput) && isset($responseObject->statusOutput->isRefundable)) {
                    if ($responseObject->statusOutput->isRefundable) {
                        return null;
                    }
                }
            }

            $subject->removeButton('credit-memo');
        }

        return null;
    }
}
