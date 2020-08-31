<?php

namespace Ingenico\Connect\Model\Ingenico\Action;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\Status\Payment\ResolverInterface;
use Ingenico\Connect\Model\StatusResponseManager;
use Ingenico\Connect\Model\Transaction\TransactionManager;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__cancel_post
 */
class CancelPayment extends AbstractAction implements ActionInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * CancelPayment constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param OrderRepositoryInterface $orderRepository
     * @param ResolverInterface $statusResolver
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        OrderRepositoryInterface $orderRepository,
        ResolverInterface $statusResolver
    ) {
        $this->orderRepository = $orderRepository;
        $this->statusResolver = $statusResolver;

        parent::__construct(
            $statusResponseManager,
            $ingenicoClient,
            $transactionManager,
            $config
        );
    }

    /**
     * Cancel payment
     *
     * @param Order $order
     * @throws LocalizedException
     * @throws ResponseException
     */
    public function process(Order $order)
    {
        /** @var Order\Payment $payment */
        $payment = $order->getPayment();

        $transactionId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);
        $authResponseObject = $this->statusResponseManager->get($payment, $transactionId);
        $ingenicoPaymentId = $authResponseObject->id;

        $response = $this->ingenicoClient->ingenicoPaymentCancel($ingenicoPaymentId);

        // update order status to cancel
        $this->statusResolver->resolve($order, $response->payment);

        $transaction = $this->transactionManager->retrieveTransaction($transactionId);
        if ($transaction !== null) {
            $transaction->setIsClosed(true);
        }
        $order->addRelatedObject($transaction);

        $this->orderRepository->save($order);

        $this->postProcess($payment, $response->payment);
    }
}
