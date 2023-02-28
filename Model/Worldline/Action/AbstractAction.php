<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Action;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;

abstract class AbstractAction
{
    /**
     * @var StatusResponseManager
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $statusResponseManager;

    /**
     * @var ClientInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $worldlineClient;

    /**
     * @var TransactionManager
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $transactionManager;

    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $config;

    /**
     * AbstractAction constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $worldlineClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $worldlineClient,
        TransactionManager $transactionManager,
        ConfigInterface $config
    ) {
        $this->statusResponseManager = $statusResponseManager;
        $this->worldlineClient = $worldlineClient;
        $this->transactionManager = $transactionManager;
        $this->config = $config;
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param Payment $payment
     * @param \Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment
     * | \Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse
     * | \Ingenico\Connect\Sdk\Domain\Refund\RefundResponse $response
     * @throws LocalizedException
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    protected function postProcess(
        Payment $payment,
        $response
    ) {
        $payment->setTransactionId($response->id);
        $this->statusResponseManager->set($payment, $response->id, $response);
    }
}
