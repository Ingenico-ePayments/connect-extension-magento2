<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Action;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Gateway\Command\CreateHostedCheckoutRequestBuilder;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;

class CreateHostedCheckout extends AbstractAction
{
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $worldlineClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        private readonly ClientInterface $client,
        private readonly CreateHostedCheckoutRequestBuilder $createHostedCheckoutRequestBuilder,
    ) {
        parent::__construct($statusResponseManager, $worldlineClient, $transactionManager, $config);
    }

    public function process(Payment $payment, bool $requiresApproval): void
    {
        $order = $payment->getOrder();
        $payment->setIsTransactionClosed(false);
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->setStatus(Order::STATE_PENDING_PAYMENT);

        $response = $this->client->createHostedCheckout(
            $this->createHostedCheckoutRequestBuilder->build($payment, $requiresApproval),
            $order->getStoreId()
        );

        $checkoutSubdomain = $this->config->getHostedCheckoutSubDomain($order->getStoreId());
        $worldlineRedirectUrl = $checkoutSubdomain . $response->partialRedirectUrl;

        $payment->setTransactionId($response->hostedCheckoutId);
        $payment->setAdditionalInformation(Config::REDIRECT_URL_KEY, $worldlineRedirectUrl);
        $payment->setAdditionalInformation(Config::HOSTED_CHECKOUT_ID_KEY, $response->hostedCheckoutId);
        $payment->setAdditionalInformation(Config::RETURNMAC_KEY, $response->RETURNMAC);
    }
}
