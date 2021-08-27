<?php

declare(strict_types=1);

namespace Ingenico\Connect\Plugin\Magento\Sales\Block\Adminhtml\Order;

use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\ConfigProvider;
use Magento\Framework\UrlInterface;
use Magento\Sales\Block\Adminhtml\Order\View as BaseView;
use Magento\Sales\Block\Adminhtml\Order\View as ViewBlock;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;

class View extends AbstractOrder
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        ConfigInterface $config,
        UrlInterface $urlBuilder
    ) {
        parent::__construct($config);
        $this->urlBuilder = $urlBuilder;
    }

    public function beforeAddButton(
        BaseView $subject,
        ...$args
    ) {
        if ($subject->getOrder()->getPayment()->getMethod() !== ConfigProvider::CODE) {
            return $args;
        }

        if ($args[0] === 'order_creditmemo') {
            if ($this->allowOfflineRefund($subject->getOrder())) {
                $this->addExtraOptionsToCreditMemoDialog($args[1], $subject);
            } else {
                $this->updateCreditMemoButton($args[1], $subject);
            }
        }

        return $args;
    }

    /**
     * Add button before set layout
     *
     * @param ViewBlock $view
     */
    public function beforeSetLayout(ViewBlock $view)
    {
        $order = $view->getOrder();
        // Only proceed with Ingenico orders
        if ($order->getPayment()->getMethodInstance()->getCode() === ConfigProvider::CODE) {
            $this->addRefreshOrderButton($view);
            // Only proceed with suspected fraud orders
            if ($view->getOrder()->getStatus() === Order::STATUS_FRAUD) {
                $this->updateApproveFraudPaymentButton($view);
            }
        }
    }

    /**
     * @param ViewBlock $view
     */
    private function addRefreshOrderButton(ViewBlock $view)
    {
        $urlRefreshOrder = $this->urlBuilder->getUrl(
            'epayments/OrderProcess/RefreshOrderStatus/',
            ['order_id' => $view->getOrderId()]
        );
        $view->addButton(
            'refresh_order_payment',
            [
                'label' => __('Refresh Order Status'),
                'onclick' => "setLocation('$urlRefreshOrder')",
            ]
        );
    }

    /**
     * @param ViewBlock $view
     */
    private function updateApproveFraudPaymentButton(ViewBlock $view)
    {
        $urlPaymentAccept = $this->urlBuilder->getUrl(
            'epayments/OrderProcess/ApprovePayment/',
            ['order_id' => $view->getOrderId()]
        );
        $view->updateButton(
            'accept_payment',
            'onclick',
            "confirmSetLocation('Are you sure you want to accept this payment?', '$urlPaymentAccept')"
        );
    }

    private function addExtraOptionsToCreditMemoDialog(array &$buttonData, BaseView $subject)
    {
        $title = __('Create a Credit Memo');
        $content = __('Are you sure?')->render() . '<br/><br/>';
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $content .= __('This will create an offline refund that will not be communicated to Ingenico. If you want to perform a refund using the services of Ingenico, you need to create a credit memo on the invoice.')->render();
        $content .= '<ul style="margin: 1em;">';
        $content .= '<li><a href="' . $subject->getCreditmemoUrl() . '">' .
            __('I understand and I want to create an offline refund')->render() . '</a></li>';
        /** @var Invoice $invoice */
        $invoiceUrl = $this->getInvoiceUrl($subject);
        if ($invoiceUrl !== '') {
            $content .= '<li><a href="' . $invoiceUrl . '">' .
                __('I want to create a credit memo on the invoice')->render() . '</a></li>';
        }
        $content .= '</ul>';
        $buttonData['onclick'] = 'require(\'Magento_Ui/js/modal/alert\')({title:\'' .
            $title . '\', content:\'' . $content . '\', ' .
            'buttons:[{text: \'Cancel\',class: \'action-primary action-accept\',' .
            'click: function () {this.closeModal(true);}}]})';
    }

    private function updateCreditMemoButton(array &$buttonData, BaseView $subject)
    {
        $invoiceUrl = $this->getInvoiceUrl($subject);
        if ($invoiceUrl !== '') {
            $buttonData['onclick'] = 'window.location=\'' . $invoiceUrl . '\'';
        }
    }

    private function getInvoiceUrl(BaseView $subject): string
    {
        /** @var Invoice $invoice */
        $invoice = $subject->getOrder()->getInvoiceCollection()->getFirstItem();
        if ($invoice instanceof Invoice) {
            return $this->urlBuilder->getUrl(
                'sales/order_creditmemo/new',
                [
                    'order_id' => $subject->getOrderId(),
                    'invoice_id' => $invoice->getId(),
                ]
            );
        }
        return '';
    }
}
