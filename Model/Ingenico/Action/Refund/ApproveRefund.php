<?php

namespace Netresearch\Epayments\Model\Ingenico\Action\Refund;

use Ingenico\Connect\Sdk\Domain\Refund\ApproveRefundRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Netresearch\Epayments\Helper\Data as DataHelper;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\ConfigProvider;
use Netresearch\Epayments\Model\Ingenico\Action\AbstractAction;
use Netresearch\Epayments\Model\Ingenico\Action\ActionInterface;
use Netresearch\Epayments\Model\Ingenico\Action\RetrievePayment;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\Ingenico\Status\ResolverInterface;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;
use Netresearch\Epayments\Model\StatusResponseManager;
use Netresearch\Epayments\Model\Transaction\TransactionManager;

class ApproveRefund extends AbstractAction implements ActionInterface
{
    /**
     * @var string[]
     */
    private $allowedStates = [StatusInterface::PENDING_APPROVAL];

    /**
     * @var RetrievePayment
     */
    private $retrievePayment;

    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * @var ApproveRefundRequest
     */
    private $approveRefundRequest;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * ApproveRefund constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param ApproveRefundRequest $approveRefundRequest
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param RetrievePayment $retrievePayment
     * @param ResolverInterface $statusResolver
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        ApproveRefundRequest $approveRefundRequest,
        CreditmemoRepositoryInterface $creditmemoRepository,
        RetrievePayment $retrievePayment,
        ResolverInterface $statusResolver
    ) {
        $this->approveRefundRequest = $approveRefundRequest;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->retrievePayment = $retrievePayment;
        $this->statusResolver = $statusResolver;

        parent::__construct($statusResponseManager, $ingenicoClient, $transactionManager, $config);
    }

    /**
     * Approve the creditmemo at the Ingenico API
     *
     * @param Creditmemo $creditmemo
     * @throws LocalizedException
     */
    public function process(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $refundId = $creditmemo->getTransactionId();
        $payment = $order->getPayment();

        $refundResponse = $this->statusResponseManager->get($payment, $refundId);

        $isAllowedStatus = in_array(
            $refundResponse->status,
            $this->allowedStates
        );
        if (!$isAllowedStatus && $this->isIngenicoOpenRefund($creditmemo)) {
            throw new LocalizedException(__("Cannot approve refund with status $refundResponse->status"));
        }

        // Approve refund via Ingenico API
        $this->approveRefund($creditmemo);

        // Retrieve current status from api because
        // approveRefund only returns a HTTP status code
        $this->retrievePayment->process($order);
        $refundResponse = $this->statusResponseManager->get($payment, $refundId);

        $this->statusResolver->resolve($order, $refundResponse);
        // update refund status
        $creditmemo->setState(Creditmemo::STATE_REFUNDED);
        $this->creditmemoRepository->save($creditmemo);
    }

    /**
     * @param Creditmemo $creditmemo
     */
    private function approveRefund(Creditmemo $creditmemo)
    {
        $amount = DataHelper::formatIngenicoAmount($creditmemo->getGrandTotal());

        $this->approveRefundRequest->amount = $amount;

        $this->ingenicoClient->ingenicoRefundAccept(
            $creditmemo->getTransactionId(),
            $this->approveRefundRequest,
            $creditmemo->getStoreId()
        );
    }

    /**
     * Check if it's ingenico payment and refund status is OPEN
     *
     * @param Creditmemo $creditmemo
     * @return bool
     */
    private function isIngenicoOpenRefund(Creditmemo $creditmemo)
    {
        $methodCode = $creditmemo->getOrder()->getPayment()->getMethodInstance()->getCode();

        return $methodCode === ConfigProvider::CODE && $creditmemo->getState() === Creditmemo::STATE_OPEN;
    }
}
