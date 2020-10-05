<?php

declare(strict_types=1);

namespace Ingenico\Connect\Plugin\Magento\Sales\Controller\Adminhtml\Order;

use Exception;
use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\Connect\Model\Ingenico\Action\RetrievePayment;
use Ingenico\Connect\Model\Ingenico\StatusInterface;
use Ingenico\Connect\Api\OrderPaymentManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\View as ViewController;

class View
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RetrievePayment
     */
    private $retrievePayment;

    /**
     * @var OrderPaymentManagementInterface
     */
    private $orderPaymentManagement;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderPaymentManagementInterface $orderPaymentManagement,
        RetrievePayment $retrievePayment
    ) {
        $this->orderRepository = $orderRepository;
        $this->retrievePayment = $retrievePayment;
        $this->orderPaymentManagement = $orderPaymentManagement;
    }

    public function beforeExecute(ViewController $subject)
    {
        $id = $subject->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($id);
        $payment = $order->getPayment();

        try {
            if ($payment->getMethod() === ConfigProvider::CODE &&
                $this->orderPaymentManagement->getIngenicoPaymentStatus($payment) === StatusInterface::CAPTURE_REQUESTED
            ) {
                $this->retrievePayment->process($order);
            }
        } catch (Exception $exception) {
            // An exception should never break the flow of viewing an order
            return null;
        }

        return null;
    }
}
