<?php

namespace Netresearch\Epayments\Model\OrderUpdate;

use Magento\Framework\Logger\Monolog;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\OrderRepository;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\Ingenico\Status\ResolverInterface;

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
     * @var int
     */
    private $scopeId;

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
        $this->scopeId = $order->getStoreId();
        $orderId = $order->getEntityId();
        $paymentId = @unserialize($order->getAdditionalInformation())[Config::PAYMENT_ID_KEY];
        $this->logger->info("--- orderId $orderId, paymentId $paymentId");

        // skip order if it's not time to pull
        if (!$this->scheduler->timeForAttempt($order)) {
            $this->logger->info("skipped, not a time yet");

            return;
        }

        try {
            // call ingenico
            /** @var \Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse $response */
            $response = $this->getIngenicoPayment($paymentId);

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
     * Api call to ingenico to get payment details
     *
     * @param $ingenicoPaymentId
     * @return \Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse
     */
    private function getIngenicoPayment($ingenicoPaymentId)
    {
        $response = $this->ingenicoClient->getIngenicoClient($this->scopeId)
                                         ->merchant($this->config->getMerchantId($this->scopeId))
                                         ->payments()
                                         ->get($ingenicoPaymentId);

        return $response;
    }
}
