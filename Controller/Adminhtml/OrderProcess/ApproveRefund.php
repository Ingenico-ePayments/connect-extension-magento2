<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Controller\Adminhtml\OrderProcess;

use Ingenico\Connect\Sdk\Domain\Errors\Definitions\APIError;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Psr\Log\LoggerInterface;
use Worldline\Connect\Model\Worldline\Action\Refund\ApproveRefund as ApproveRefundAction;

class ApproveRefund extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Sales::sales_creditmemo';

    /** @var CreditmemoRepositoryInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $creditmemoRepository;

    /** @var ApproveRefundAction */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $approveRefund;

    /** @var LoggerInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Approve refund
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function execute()
    {
        $creditmemoId = $this->getRequest()->getParam('creditmemo_id');

        try {
            /** @var Creditmemo $creditMemo */
            $creditMemo = $this->creditmemoRepository->get($creditmemoId);

            $this->approveRefund->process($creditMemo);

            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $this->messageManager->addSuccessMessage(__('The refund was approved.'));
        } catch (ResponseException $e) {
            $errors = $e->getErrors();
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $message = array_reduce(
                $errors,
                // phpcs:ignore SlevomatCodingStandard.Functions.StaticClosure.ClosureNotStatic
                function (
                    $message,
                    APIError $error
                ) {
                    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
