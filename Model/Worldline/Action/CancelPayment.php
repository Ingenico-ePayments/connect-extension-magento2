<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Action;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\ResolverInterface;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__cancel_post
 */
class CancelPayment extends AbstractAction implements ActionInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderRepository;

    /**
     * CancelPayment constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $worldlineClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param OrderRepositoryInterface $orderRepository
     * @param ResolverInterface $statusResolver
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $worldlineClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;

        parent::__construct(
            $statusResponseManager,
            $worldlineClient,
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
        $worldlinePaymentId = $authResponseObject->id;

        $response = $this->worldlineClient->worldlinePaymentCancel($worldlinePaymentId);

        $transaction = $this->transactionManager->retrieveTransaction($transactionId);
        if ($transaction !== null) {
            $transaction->setIsClosed(true);
        }
        $order->addRelatedObject($transaction);

        $this->orderRepository->save($order);

        $this->postProcess($payment, $response->payment);
    }
}
