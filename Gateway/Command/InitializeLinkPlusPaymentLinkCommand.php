<?php

declare(strict_types=1);

namespace Worldline\Connect\Gateway\Command;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Client;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Helper\Data as DataHelper;

class InitializeLinkPlusPaymentLinkCommand implements CommandInterface
{
    public function execute(array $commandSubject)
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $order = $payment->getOrder();
        $method = $payment->getMethodInstance();

        $expiry = new DateTimeImmutable('now', new DateTimeZone('Europe/Amsterdam'));

        $client = new Client();
        $client->request('POST', $method->getConfigData('url'), [
            'headers' => [
                'x-api-key' => $method->getConfigData('api_key'),
            ],
            'form_params' => [
                'type' => 'elink',
                'sendEmail' => 'Yes',
                'expiry' => $expiry->add(new DateInterval('PT12H'))->format('YmdHi'),
                'locale' => $method->getConfigData('locale'),
                'amount' => DataHelper::formatWorldlineAmount($order->getGrandTotal()),
                'currencyCode' => $order->getOrderCurrencyCode(),
                'merchantReference' => $order->getIncrementId(),
                'emailAddress' => $order->getBillingAddress()->getEmail(),
                'eLinkTemplate' => 'default',
            ],
        ]);

        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $payment->setIsTransactionClosed(false);
    }
}
