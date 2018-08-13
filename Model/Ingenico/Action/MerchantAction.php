<?php

namespace Netresearch\Epayments\Model\Ingenico\Action;

use Ingenico\Connect\Sdk\Domain\Hostedcheckout\Definitions\DisplayedDataFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\Config;

class MerchantAction implements ActionInterface
{
    const ACTION_TYPE_REDIRECT = 'REDIRECT';
    const ACTION_TYPE_SHOW_FORM = 'SHOW_FORM';
    const ACTION_TYPE_SHOW_INSTRUCTIONS = 'SHOW_INSTRUCTIONS';
    const ACTION_TYPE_SHOW_TRANSACTION_RESULTS = 'SHOW_TRANSACTION_RESULTS';

    /**
     * @var DisplayedDataFactory
     */
    private $displayedDataFactory;

    /**
     * MerchantAction constructor.
     *
     * @param DisplayedDataFactory $displayedDataFactory
     */
    public function __construct(DisplayedDataFactory $displayedDataFactory)
    {
        $this->displayedDataFactory = $displayedDataFactory;
    }

    /**
     * @param Order $order
     * @param \Ingenico\Connect\Sdk\Domain\Payment\Definitions\MerchantAction $merchantAction
     *
     * @url https://epayments-api.developer-ingenico.com/s2sapi/v1/en_US/java/payments/create.html#payments-create-response-201
     */
    public function handle(
        Order $order,
        \Ingenico\Connect\Sdk\Domain\Payment\Definitions\MerchantAction $merchantAction
    ) {
        switch ($merchantAction->actionType) {
            case self::ACTION_TYPE_REDIRECT:
                $url = $merchantAction->redirectData->redirectURL;
                $returnmac = $merchantAction->redirectData->RETURNMAC;
                $order->getPayment()->setAdditionalInformation(Config::RETURNMAC_KEY, $returnmac);
                $order->getPayment()->setAdditionalInformation(Config::REDIRECT_URL_KEY, $url);
                break;
            case self::ACTION_TYPE_SHOW_INSTRUCTIONS:
                $displayData = $this->displayedDataFactory->create();
                $displayData->fromObject($merchantAction);
                $order->getPayment()->setAdditionalInformation(
                    Config::PAYMENT_SHOW_DATA_KEY,
                    $displayData->toJson()
                );
                break;
            case self::ACTION_TYPE_SHOW_TRANSACTION_RESULTS:
                $data = [];
                foreach ($merchantAction->showData as $item) {
                    if ($item->key !== 'BARCODE') {
                        $data[] = $item->key . ': ' . $item->value;
                    }
                }
                $order->getPayment()->setAdditionalInformation(Config::TRANSACTION_RESULTS_KEY, implode('; ', $data));
                break;
            case self::ACTION_TYPE_SHOW_FORM:
                /** @TODO(nr) Implement form field page for Bancontact. */
        }
    }
}
