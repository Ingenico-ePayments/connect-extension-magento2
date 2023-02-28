<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Action;

use Ingenico\Connect\Sdk\Domain\Hostedcheckout\Definitions\DisplayedDataFactory;
use Magento\Sales\Model\Order;
use Worldline\Connect\Model\Config;

class MerchantAction implements ActionInterface
{
    public const ACTION_TYPE_REDIRECT = 'REDIRECT';
    public const ACTION_TYPE_SHOW_FORM = 'SHOW_FORM';
    public const ACTION_TYPE_SHOW_INSTRUCTIONS = 'SHOW_INSTRUCTIONS';
    public const ACTION_TYPE_SHOW_TRANSACTION_RESULTS = 'SHOW_TRANSACTION_RESULTS';

    /**
     * @var DisplayedDataFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param Order $order
     * @param \Ingenico\Connect\Sdk\Domain\Payment\Definitions\MerchantAction $merchantAction
     *
     * @url https://epayments-api.developer-ingenico.com/s2sapi/v1/en_US/java/payments/create.html#payments-create-response-201
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded, SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function handle(
        Order $order,
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        \Ingenico\Connect\Sdk\Domain\Payment\Definitions\MerchantAction $merchantAction
    ) {
        switch ($merchantAction->actionType) {
            case self::ACTION_TYPE_REDIRECT:
                $url = $merchantAction->redirectData->redirectURL;
                $returnmac = $merchantAction->redirectData->RETURNMAC;
                $order->getPayment()->setAdditionalInformation(Config::RETURNMAC_KEY, $returnmac);
                $order->getPayment()->setAdditionalInformation(Config::REDIRECT_URL_KEY, $url);
//                $order->getPayment()->setIsTransactionPending(true);
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
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                $order->getPayment()->setAdditionalInformation(Config::TRANSACTION_RESULTS_KEY, implode('; ', $data));
                break;
            case self::ACTION_TYPE_SHOW_FORM:
                // phpcs:ignore Generic.Commenting.Todo.TaskFound
                /** @TODO(nr) Implement form field page for Bancontact. */
        }
    }
}
