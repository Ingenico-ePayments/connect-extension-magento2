<?php

namespace Netresearch\Epayments\Controller\Adminhtml\OrderProcess;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\Ingenico\Action\ApproveChallengedPayment;
use Psr\Log\LoggerInterface;

class ApprovePayment extends Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::review_payment';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ApproveChallengedPayment
     */
    private $approveChallengedPayment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ApprovePayment constructor.
     *
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param ApproveChallengedPayment $approveChallengedPayment
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        ApproveChallengedPayment $approveChallengedPayment,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->approveChallengedPayment = $approveChallengedPayment;
        $this->logger = $logger;
    }

    /**
     * Accept suspected fraud payment.
     * If "direct capture" setting is enabled, this will also capture the payment and create an invoice.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        /** @var Order $order */
        $order = $this->orderRepository->get($orderId);

        try {
            $this->approveChallengedPayment->process($order);
            $this->messageManager->addSuccessMessage(__('Approved the payment online.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to approve the order.'));
            $this->logger->error($e->getMessage());
        }

        $this->orderRepository->save($order);

        return $this->redirect();
    }

    /**
     * Return redirect object to referrer
     *
     * @return Redirect
     */
    private function redirect()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}
