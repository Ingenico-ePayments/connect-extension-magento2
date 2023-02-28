<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Controller\Adminhtml\OrderProcess;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Worldline\Connect\Model\Worldline\Action\ApproveChallengedPayment;

class ApprovePayment extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Sales::review_payment';

    /**
     * @var OrderRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderRepository;

    /**
     * @var ApproveChallengedPayment
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $approveChallengedPayment;

    /**
     * @var LoggerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $this->messageManager->addSuccessMessage(__('Approved the payment online.'));
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        } catch (\Exception $e) {
            throw $e;
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
