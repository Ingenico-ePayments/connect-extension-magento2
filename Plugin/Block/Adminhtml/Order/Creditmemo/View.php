<?php

namespace Netresearch\Epayments\Plugin\Block\Adminhtml\Order\Creditmemo;

use Magento\Framework\UrlInterface;
use Netresearch\Epayments\Model\ConfigProvider;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;
use Netresearch\Epayments\Model\StatusResponseManager;

class View
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var StatusResponseManager
     */
    private $statusResponseManager;

    /**
     * View constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param StatusResponseManager $statusResponseManager
     */
    public function __construct(
        UrlInterface $urlBuilder,
        StatusResponseManager $statusResponseManager
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->statusResponseManager = $statusResponseManager;
    }

    /**
     * Add button before set layout
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Creditmemo\View $view
     */
    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\Creditmemo\View $view)
    {
        $creditmemo = $view->getCreditmemo();
        $payment = $creditmemo->getOrder()->getPayment();
        $paymentMethod = $payment->getMethodInstance();
        $isHostedCheckout = $paymentMethod->getCode() == ConfigProvider::CODE;

        if (!$isHostedCheckout) {
            return;
        }

        $status = $this->statusResponseManager->get($payment, $creditmemo->getTransactionId());

        if (!$status) {
            return;
        }

        $isRefundRequested = $status->status === StatusInterface::REFUND_REQUESTED;
        $isRefundPending = $status->status === StatusInterface::PENDING_APPROVAL;
        $isCancelAllowed = $status->statusOutput->isCancellable;

        if (($isRefundRequested || $isRefundPending) && $isCancelAllowed) {
            $this->updateCancelButton($view);
        } else {
            $view->removeButton('cancel');
        }
        if ($isRefundPending) {
            $this->addAcceptRefundButton($view);
        }
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Creditmemo\View $view
     */
    protected function updateCancelButton(\Magento\Sales\Block\Adminhtml\Order\Creditmemo\View $view)
    {
        $urlRefundCancel = $this->urlBuilder->getUrl(
            'epayments/OrderProcess/CancelRefund/',
            ['creditmemo_id' => $view->getCreditmemo()->getId()]
        );
        $view->updateButton(
            'cancel',
            'onclick',
            "confirmSetLocation('Are you sure you want to cancel the refund?', '$urlRefundCancel')"
        );
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Creditmemo\View $view
     */
    protected function addAcceptRefundButton(\Magento\Sales\Block\Adminhtml\Order\Creditmemo\View $view)
    {
        $urlRefundAccept = $this->urlBuilder->getUrl(
            'epayments/OrderProcess/ApproveRefund/',
            ['creditmemo_id' => $view->getCreditmemo()->getId()]
        );
        $view->addButton(
            'accept_refund',
            [
                'label' => __('Accept'),
                'class' => 'accept_refund_custom',
                'onclick' => "confirmSetLocation('Are you sure you want to accept the refund?', '$urlRefundAccept')",
            ]
        );
    }
}
