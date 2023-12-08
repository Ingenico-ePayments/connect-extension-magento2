<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Action;

use Ingenico\Connect\Sdk\Domain\Hostedcheckout\Definitions\DisplayedDataFactory;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentResponse;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\MerchantAction as MerchantActionDefinition;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Model\Config;

use function implode;

class MerchantAction implements ActionInterface
{
    public const ACTION_TYPE_REDIRECT = 'REDIRECT';
    public const ACTION_TYPE_SHOW_FORM = 'SHOW_FORM';
    public const ACTION_TYPE_SHOW_INSTRUCTIONS = 'SHOW_INSTRUCTIONS';
    public const ACTION_TYPE_SHOW_TRANSACTION_RESULTS = 'SHOW_TRANSACTION_RESULTS';

    public function __construct(
        private readonly DisplayedDataFactory $displayedDataFactory,
    ) {
    }

    public function handle(Payment $payment, CreatePaymentResponse $createPaymentResponse)
    {
        if ($createPaymentResponse->merchantAction === null) {
            return;
        }

        $merchantAction = $createPaymentResponse->merchantAction;
        if ($merchantAction->actionType === null) {
            return;
        }

        match ($merchantAction->actionType) {
            self::ACTION_TYPE_REDIRECT => $this->handleRedirect($merchantAction, $payment),
            self::ACTION_TYPE_SHOW_INSTRUCTIONS => $this->handleShowInstructions($merchantAction, $payment),
            self::ACTION_TYPE_SHOW_TRANSACTION_RESULTS => $this->handleShowTransaction($merchantAction, $payment),
        };
    }

    private function getShowData(MerchantActionDefinition $merchantAction): array
    {
        $data = [];
        foreach ($merchantAction->showData as $item) {
            if ($item->key !== 'BARCODE') {
                $data[] = $item->key . ': ' . $item->value;
            }
        }
        return $data;
    }

    private function handleRedirect(MerchantActionDefinition $merchantAction, Payment $payment): void
    {
        $url = $merchantAction->redirectData->redirectURL;
        $returnmac = $merchantAction->redirectData->RETURNMAC;
        $payment->setAdditionalInformation(Config::RETURNMAC_KEY, $returnmac);
        $payment->setAdditionalInformation(Config::REDIRECT_URL_KEY, $url);
    }

    private function handleShowInstructions(MerchantActionDefinition $merchantAction, Payment $payment): void
    {
        $displayData = $this->displayedDataFactory->create();
        $displayData->fromObject($merchantAction);
        $payment->setAdditionalInformation(
            Config::PAYMENT_SHOW_DATA_KEY,
            $displayData->toJson()
        );
    }

    private function handleShowTransaction(MerchantActionDefinition $merchantAction, Payment $payment): void
    {
        $payment->setAdditionalInformation(
            Config::TRANSACTION_RESULTS_KEY,
            implode('; ', $this->getShowData($merchantAction))
        );
    }
}
