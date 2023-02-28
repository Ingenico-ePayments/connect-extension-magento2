<?php

declare(strict_types=1);

namespace Worldline\Connect\Plugin\Magento\Sales\Block\Adminhtml\Order\Creditmemo;

use Magento\Framework\UrlInterface;
use Magento\Sales\Block\Adminhtml\Order\Creditmemo\View as BaseView;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Worldline\StatusInterface;

class View
{
    /**
     * @var UrlInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $urlBuilder;

    /**
     * @var StatusResponseManager
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
     * @param BaseView $view
     */
    public function beforeSetLayout(BaseView $view)
    {
        $creditmemo = $view->getCreditmemo();
        $payment = $creditmemo->getOrder()->getPayment();
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
     * @param BaseView $view
     */
    private function updateCancelButton(BaseView $view)
    {
        $urlRefundCancel = $this->urlBuilder->getUrl(
            'epayments/OrderProcess/CancelRefund/',
            ['creditmemo_id' => $view->getCreditmemo()->getId()]
        );
        $view->updateButton(
            'cancel',
            'onclick',
            // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
            "confirmSetLocation('Are you sure you want to cancel the refund?', '$urlRefundCancel')"
        );
    }

    /**
     * @param BaseView $view
     */
    private function addAcceptRefundButton(BaseView $view)
    {
        $urlRefundAccept = $this->urlBuilder->getUrl(
            'epayments/OrderProcess/ApproveRefund/',
            ['creditmemo_id' => $view->getCreditmemo()->getId()]
        );
        $view->addButton(
            'accept_refund',
            [
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                'label' => __('Accept'),
                'class' => 'accept_refund_custom',
                // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
                'onclick' => "confirmSetLocation('Are you sure you want to accept the refund?', '$urlRefundAccept')",
            ]
        );
    }
}
