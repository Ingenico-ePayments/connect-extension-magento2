<?php

namespace Netresearch\Epayments\Controller\Adminhtml\OrderProcess;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\Ingenico\Action\RetrievePayment;
use Psr\Log\LoggerInterface;

class RefreshOrderStatus extends Action
{
    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var RetrievePayment */
    private $retrievePayment;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param RetrievePayment $retrievePayment
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        RetrievePayment $retrievePayment,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->retrievePayment = $retrievePayment;
        $this->logger = $logger;
    }

    /**
     * Refresh one order action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        try {
            /** @var Order $order */
            $order = $this->orderRepository->get($orderId);

            /** @var bool $orderWasUpdated */
            $orderWasUpdated = $this->retrievePayment->process($order);

            if ($orderWasUpdated) {
                $this->messageManager->addSuccessMessage(__('The order status was successfully refreshed.'));
            } else {
                $this->messageManager
                    ->addWarningMessage(__('There is nothing to update. Payment status was not changed.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Unable to refresh the order.');
            $this->logger->error($e->getMessage());
        }

        // redirect to referrer
        return $this->redirect();
    }

    /**
     * Return redirect object
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function redirect()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}
