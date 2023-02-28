<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Controller\Adminhtml\OrderProcess;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Worldline\Connect\Model\Worldline\Action\RetrievePayment;

class RefreshOrderStatus extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Sales::sales_invoice';

    /** @var OrderRepositoryInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderRepository;

    /** @var RetrievePayment */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $retrievePayment;

    /** @var LoggerInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Refresh one order action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        try {
            /** @var Order $order */
            $order = $this->orderRepository->get($orderId);

            /** @var bool $orderWasUpdated */
            $orderWasUpdated = $this->retrievePayment->process($order);

            if ($orderWasUpdated) {
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                $this->messageManager->addSuccessMessage(__('The order status was successfully refreshed.'));
            } else {
                $this->messageManager
                    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                    ->addWarningMessage(__('There is nothing to update. Payment status was not changed.'));
            }
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Unable to refresh the order.');
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
