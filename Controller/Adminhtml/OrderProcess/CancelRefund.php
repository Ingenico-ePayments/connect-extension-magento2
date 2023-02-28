<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Controller\Adminhtml\OrderProcess;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Psr\Log\LoggerInterface;

class CancelRefund extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Sales::sales_creditmemo';

    /** @var CreditmemoRepositoryInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $creditmemoRepository;

    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /** @var \Worldline\Connect\Model\Worldline\Action\Refund\CancelRefund */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $refundCancel;

    /** @var LoggerInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $logger;

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param Context $context
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param \Worldline\Connect\Model\Worldline\Action\Refund\CancelRefund $refundCancel
     * @param LoggerInterface $logger
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function __construct(
        Context $context,
        CreditmemoRepositoryInterface $creditmemoRepository,
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        \Worldline\Connect\Model\Worldline\Action\Refund\CancelRefund $refundCancel,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->creditmemoRepository = $creditmemoRepository;
        $this->refundCancel = $refundCancel;
        $this->logger = $logger;
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Cancel refund
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function execute()
    {
        $creditmemoId = $this->getRequest()->getParam('creditmemo_id');

        try {
            /** @var Creditmemo $creditMemo */
            $creditMemo = $this->creditmemoRepository->get($creditmemoId);
            $this->refundCancel->process($creditMemo);
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $this->messageManager->addSuccessMessage(__('The refund was cancelled.'));
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        } catch (\Exception $e) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $this->messageManager->addErrorMessage(__('Unable to refresh the refund.'));
            $this->logger->error($e->getMessage());
        }

        // redirect to referrer
        return $this->redirect();
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Return redirect object
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    private function redirect()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}
