<?php

namespace Ingenico\Connect\Cron\FetchWxFiles;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Logger\Monolog;
use Ingenico\Connect\Model\Ingenico\GlobalCollect\StatusBuilder;
use Ingenico\Connect\Model\Ingenico\GlobalCollect\Wx\DataRecord;
use Ingenico\Connect\WxTransfer\ClientInterface;

class Processor implements ProcessorInterface
{
    /**
     * @var ClientInterface
     */
    private $wxClient;

    /**
     * @var Monolog
     */
    private $logger;

    /**
     * @var StatusBuilder
     */
    private $statusBuilder;

    /**
     * @var StatusUpdateResolverInterface
     */
    private $statusUpdateResolver;

    /**
     * Processor constructor.
     *
     * @param ClientInterface $wxClient
     * @param Monolog $logger
     * @param StatusUpdateResolverInterface $statusUpdateResolver
     * @param StatusBuilder $statusBuilder
     */
    public function __construct(
        ClientInterface $wxClient,
        Monolog $logger,
        StatusUpdateResolverInterface $statusUpdateResolver,
        StatusBuilder $statusBuilder
    ) {
        $this->wxClient = $wxClient;
        $this->logger = $logger;
        $this->statusBuilder = $statusBuilder;
        $this->statusUpdateResolver = $statusUpdateResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function process($scopeId, $date = 'yesterday')
    {
        // get xml object for scope
        try {
            $responseXml = $this->wxClient->loadDailyWx($date, $scopeId);
        } catch (LocalizedException $exception) {
            $this->logger->addError($exception->getMessage(), ['exception' => $exception]);
            return;
        }

        if (!$responseXml || $responseXml->getElementsByTagName('NumberOfRecords')->item(0)->nodeValue === 0) {
            // No Data to process
            $this->logger->addInfo('No file or entries found, aborting');
            return;
        }

        $transactionEntries = $responseXml->getElementsByTagName('DataRecord');

        $statusList = $this->emulatePaymentResponse($transactionEntries);
        if (!empty($statusList)) {
            $this->logger->info(sprintf(
                "Found informations about the following orders: %s",
                implode(', ', array_keys($statusList))
            ));

            $updatedOrders = $this->statusUpdateResolver->resolveBatch($statusList);
            if (!empty($updatedOrders)) {
                $this->logger->info(sprintf(
                    "Successfully updated the following orders: %s",
                    implode(', ', $updatedOrders)
                ));
            } else {
                $this->logger->info('No update performed.');
            }
        } else {
            $this->logger->info('No relevant entries.');
        }
    }

    /**
     * @param $transactionEntries
     * @return AbstractOrderStatus[]    [OrderIncrementId => AbstractOrderStatus]
     */
    private function emulatePaymentResponse($transactionEntries)
    {
        $statusObjects = [];
        /** @var \DOMElement $dataRecord */
        foreach ($transactionEntries as $dataRecord) {
            $record = DataRecord::fromDomElement($dataRecord);

            if ($record->getPaymentData()->getRecordcategory() === 'X') {
                // Recordcategory X means that no actual influence on the amounts is due yet
                continue;
            }
            try {
                $emulatedResponse = $this->statusBuilder->create($record);
                if ($emulatedResponse) {
                    $statusObjects[$record->getPaymentData()->getAdditionalReference()] = $emulatedResponse;
                }
            } catch (LocalizedException $exception) {
                $this->logger->addError($exception->getMessage(), ['exception' => $exception]);
            }
        }
        return $statusObjects;
    }
}
