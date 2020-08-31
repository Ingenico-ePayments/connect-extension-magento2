<?php

namespace Ingenico\Connect\Model\OrderUpdate;

use Magento\Framework\Logger\Monolog;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\OrderRepository;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\Status\Payment\ResolverInterface;

class Order implements OrderInterface
{
    const STATUS_WAIT = 'wait';
    const STATUS_FINISHED = 'finished';

    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * @var SchedulerInterface
     */
    private $scheduler;

    /**
     * @var HistoryManagerInterface
     */
    private $historyApi;

    /**
     * @var ClientInterface
     */
    private $ingenicoClient;

    /**
     * @var Monolog
     */
    private $logger;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param ResolverInterface $resolver
     * @param SchedulerInterface $scheduler
     * @param HistoryManagerInterface $historyManager
     * @param ClientInterface $client
     * @param Monolog $logger
     * @param ConfigInterface $config
     * @param OrderRepository $orderRepository
     * @param DateTime $dateTime
     */
    public function __construct(
        ResolverInterface $resolver,
        SchedulerInterface $scheduler,
        HistoryManagerInterface $historyManager,
        ClientInterface $client,
        Monolog $logger,
        ConfigInterface $config,
        OrderRepository $orderRepository,
        DateTime $dateTime
    ) {
        $this->statusResolver = $resolver;
        $this->scheduler = $scheduler;
        $this->historyApi = $historyManager;
        $this->ingenicoClient = $client;
        $this->logger = $logger;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->dateTime = $dateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function process(\Magento\Sales\Model\Order $order)
    {
        $orderId = $order->getEntityId();
        $paymentId = $this->readPaymentId($order);
        if ($paymentId === '') {
            $this->logger->info("--- Order $orderId skipped, no Ingenico Payment ID found.");

            return;
        }

        $this->logger->info("--- Order $orderId with Ingenico Payment ID $paymentId");

        // skip order if it's not time to pull
        if (!$this->scheduler->timeForAttempt($order)) {
            $this->logger->info("Order $orderId skipped, not due yet");

            return;
        }

        // @fixme: this will only process payments, not refunds
        // also: not sure if this code is needed at all...
        // this has something to do with the webhook workaround cron :-/
        try {
            // call ingenico
            /** @var \Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse $response */
            $response = $this->getIngenicoPayment($paymentId, $order->getStoreId());

            // update order status
            $this->logger->info("ingenico status is {$response->status}");
            $this->statusResolver->resolve($order, $response);

            // update history
            $this->historyApi->addHistory(
                $order,
                [
                    'attemptTime' => $this->dateTime->timestamp(),
                    'status' => $response->status,
                    'statusCode' => $response->statusOutput->statusCode,
                    'statusCodeChangeDateTime' => $response->statusOutput->statusCodeChangeDateTime,
                ],
                HistoryManager::TYPE_API
            );
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }

        // add last attempt time
        $order->setOrderUpdateApiLastAttemptTime($this->dateTime->timestamp());

        // check if time to WR
        if ($this->scheduler->timeForWr($order)) {
            $order->setOrderUpdateWrStatus(
                self::STATUS_WAIT
            );
        }

        $this->orderRepository->save($order);
    }

    /**
     * Api call to Ingenico to get payment details
     *
     * @param string $ingenicoPaymentId
     * @param string|int $scopeId
     * @return \Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse
     */
    private function getIngenicoPayment($ingenicoPaymentId, $scopeId)
    {
        $response = $this->ingenicoClient->getIngenicoClient($scopeId)
            ->merchant($this->config->getMerchantId($scopeId))
            ->payments()
            ->get($ingenicoPaymentId);

        return $response;
    }

    /**
     * Extract AdditionalInformation JSON and try to read Ingenico Payment ID.
     * Returns empty string on failure.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    private function readPaymentId(\Magento\Sales\Model\Order $order)
    {
        $paymentId = '';
        $paymentData = json_decode($order->getAdditionalInformation(), true);
        if (isset($paymentData[Config::PAYMENT_ID_KEY])) {
            $paymentId = $paymentData[Config::PAYMENT_ID_KEY];
        }

        return $paymentId;
    }
}
