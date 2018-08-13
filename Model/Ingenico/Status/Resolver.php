<?php
namespace Netresearch\Epayments\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse;
use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\PaymentException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Netresearch\Epayments\Model\Order\Creditmemo\Service;
use Netresearch\Epayments\Model\StatusResponseManagerInterface;
use Netresearch\Epayments\Model\Transaction\TransactionManager;

/**
 * Class Resolver
 * @package Netresearch\Epayments\Model
 */
class Resolver implements ResolverInterface
{
    /**
     * @var PoolInterface
     */
    private $refundHandlerPool;
    /**
     * @var PoolInterface
     */
    private $paymentHandlerPool;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /** @var StatusResponseManagerInterface */
    private $statusResponseManager;

    /**
     * @var Service
     */
    private $creditMemoService;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Resolver constructor.
     * @param PoolInterface $refundHandlerPool
     * @param PoolInterface $paymentHandlerPool
     * @param TransactionManager $transactionManager
     * @param StatusResponseManagerInterface $statusResponseManager
     * @param Service $creditMemoService
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        PoolInterface $refundHandlerPool,
        PoolInterface $paymentHandlerPool,
        TransactionManager $transactionManager,
        StatusResponseManagerInterface $statusResponseManager,
        Service $creditMemoService,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->refundHandlerPool = $refundHandlerPool;
        $this->paymentHandlerPool = $paymentHandlerPool;
        $this->transactionManager = $transactionManager;
        $this->statusResponseManager = $statusResponseManager;
        $this->creditMemoService = $creditMemoService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Pulls the responsible StatusInterface implementation for the status and lets them handle the order transition
     *
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws NotFoundException
     * @throws PaymentException
     * @throws LocalizedException
     */
    public function resolve(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        /** @var OrderPayment $payment */
        $payment = $order->getPayment();
        if ($payment === null) {
            throw new PaymentException(
                __('No payment object on order #%id', ['id' => $order->getIncrementId()])
            );
        }
        $this->preparePayment($payment, $ingenicoStatus);

        $statusHandler = $this->getStatusHandler($ingenicoStatus);
        $statusHandler->resolveStatus($order, $ingenicoStatus);

        $this->updatePayment($payment, $ingenicoStatus);
        if ($ingenicoStatus instanceof RefundResult) {
            $this->persistCreditMemoUpdate($order);
        }
    }

    /**
     * @param AbstractOrderStatus $ingenicoStatus
     * @return HandlerInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    private function getStatusHandler(AbstractOrderStatus $ingenicoStatus)
    {
        $handler = false;
        if ($ingenicoStatus instanceof Payment || $ingenicoStatus instanceof CaptureResponse) {
            $handler = $this->paymentHandlerPool->get($ingenicoStatus->status);
        } elseif ($ingenicoStatus instanceof RefundResult) {
            $handler = $this->refundHandlerPool->get($ingenicoStatus->status);
        }
        if (!$handler) {
            throw new NotFoundException(
                __(
                    'Could not find status resolver for response %class and status %status',
                    [
                        'class' => get_class($ingenicoStatus),
                        'status' => $ingenicoStatus->status,
                    ]
                )
            );
        }
        return $handler;
    }

    /**
     * @param OrderPaymentInterface $payment
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function preparePayment(OrderPaymentInterface $payment, AbstractOrderStatus $ingenicoStatus)
    {
        $payment->setTransactionId($ingenicoStatus->id);

        if (!$this->statusResponseManager->get($payment, $ingenicoStatus->id)) {
            $this->updatePayment($payment, $ingenicoStatus);
        }
    }

    /**
     * @param OrderPaymentInterface|OrderPayment $payment
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function updatePayment(OrderPaymentInterface $payment, AbstractOrderStatus $ingenicoStatus)
    {
        $this->statusResponseManager->set(
            $payment,
            $ingenicoStatus->id,
            $ingenicoStatus
        );
    }

    /**
     * @param OrderInterface|Order $order
     * @throws NotFoundException
     */
    private function persistCreditMemoUpdate(OrderInterface $order)
    {
        /** @var OrderPayment $payment */
        $payment = $order->getPayment();

        // Save everything involved in status application
        $creditmemo = $this->creditMemoService->getCreditmemo($payment);

        if ($creditmemo->getInvoice()) {
            $order->addRelatedObject($creditmemo->getInvoice());
        }
        $order->addRelatedObject($creditmemo);
        $order->addRelatedObject($this->transactionManager->retrieveTransaction($payment->getTransactionId()));
        $order->addRelatedObject($this->transactionManager->retrieveTransaction($creditmemo->getTransactionId()));
        $order->setDataChanges(true);

        $this->orderRepository->save($order);
    }

    /**
     * @param string $type
     * @param string $status
     * @return HandlerInterface
     * @throws NotFoundException
     */
    public function getHandlerByType($type, $status)
    {
        switch ($type) {
            case self::TYPE_CAPTURE:
            case self::TYPE_PAYMENT:
                return $this->paymentHandlerPool->get($status);
            case self::TYPE_REFUND:
                return $this->refundHandlerPool->get($status);
            default:
                throw new \InvalidArgumentException('Unkown type provided.');
        }
    }
}
