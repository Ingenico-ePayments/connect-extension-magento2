<?php

namespace Netresearch\Epayments\Controller\Adminhtml\OrderProcess;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order\Creditmemo;
use Psr\Log\LoggerInterface;

class CancelRefund extends Action
{
    /** @var CreditmemoRepositoryInterface */
    private $creditmemoRepository;

    /** @var \Netresearch\Epayments\Model\Ingenico\Action\Refund\CancelRefund */
    private $refundCancel;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Context $context
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param \Netresearch\Epayments\Model\Ingenico\Action\Refund\CancelRefund $refundCancel
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        CreditmemoRepositoryInterface $creditmemoRepository,
        \Netresearch\Epayments\Model\Ingenico\Action\Refund\CancelRefund $refundCancel,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->creditmemoRepository = $creditmemoRepository;
        $this->refundCancel = $refundCancel;
        $this->logger = $logger;
    }

    /**
     * Cancel refund
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $creditmemoId = $this->getRequest()->getParam('creditmemo_id');

        try {
            /** @var Creditmemo $refund */
            $refund = $this->creditmemoRepository->get($creditmemoId);

            $this->refundCancel->process($refund);

            $this->messageManager->addSuccessMessage(__('The refund was cancelled.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to refresh the refund.'));
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
