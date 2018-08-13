<?php

namespace Netresearch\Epayments\Plugin\Block\Adminhtml\Order;

use Magento\Framework\UrlInterface;
use Magento\Sales\Block\Adminhtml\Order\View as ViewBlock;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\ConfigProvider;

class View
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * View constructor.
     *
     * @param UrlInterface $urlBuilder
     */
    public function __construct(UrlInterface $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Add button before set layout
     *
     * @param ViewBlock $view
     */
    public function beforeSetLayout(ViewBlock $view)
    {
        // Only proceed with Ingenico orders
        if ($view->getOrder()->getPayment()->getMethodInstance()->getCode() === ConfigProvider::CODE) {
            $this->addRefreshOrderButon($view);
            // Only proceed with suspected fraud orders
            if ($view->getOrder()->getStatus() === Order::STATUS_FRAUD) {
                $this->updateApproveFraudPaymentButton($view);
            }
        }
    }

    /**
     * @param ViewBlock $view
     */
    private function addRefreshOrderButon(ViewBlock $view)
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
}
