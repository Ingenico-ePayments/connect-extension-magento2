<?php

namespace Netresearch\Epayments\Controller\Adminhtml\OrderProcess;

use Ingenico\Connect\Sdk\Domain\Errors\Definitions\APIError;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order\Creditmemo;
use Netresearch\Epayments\Model\Ingenico\Action\Refund\ApproveRefund as ApproveRefundAction;
use Psr\Log\LoggerInterface;

class ApproveRefund extends Action
{
    /** @var CreditmemoRepositoryInterface */
    private $creditmemoRepository;

    /** @var ApproveRefundAction */
    private $approveRefund;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Context $context
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param ApproveRefundAction $approveRefund
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        CreditmemoRepositoryInterface $creditmemoRepository,
        ApproveRefundAction $approveRefund,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->creditmemoRepository = $creditmemoRepository;
        $this->approveRefund = $approveRefund;
        $this->logger = $logger;
    }

    /**
     * Approve refund
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $creditmemoId = $this->getRequest()->getParam('creditmemo_id');

        try {
            /** @var Creditmemo $refund */
            $refund = $this->creditmemoRepository->get($creditmemoId);

            $this->approveRefund->process($refund);

            $this->messageManager->addSuccessMessage(__('The refund was approved.'));
        } catch (ResponseException $e) {
            $errors = $e->getErrors();
            $message = array_reduce(
                $errors,
                function (
                    $message,
                    APIError $error
                ) {
                    $message .= sprintf(
                        "HTTP: %s Message: %s \n",
                        $error->httpStatusCode,
                        $error->message
                    );
                    return $message;
                },
                ''
            );
            $this->messageManager->addErrorMessage($message);
            $this->logger->error($message);
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
