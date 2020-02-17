<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\AdditionalInput\TypeInformationBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AdditionalOrderInput;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AdditionalOrderInputFactory;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Sales\Api\Data\OrderInterface;

class AdditionalInputBuilder
{
    /**
     * @var AdditionalOrderInputFactory
     */
    private $additionalOrderInputFactory;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var TypeInformationBuilder
     */
    private $typeInformationBuilder;

    public function __construct(
        AdditionalOrderInputFactory $additionalOrderInputFactory,
        DateTimeFactory $dateTimeFactory,
        TypeInformationBuilder $typeInformationBuilder
    ) {
        $this->additionalOrderInputFactory = $additionalOrderInputFactory;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->typeInformationBuilder = $typeInformationBuilder;
    }

    public function create(OrderInterface $order): AdditionalOrderInput
    {
        $additionalInput = $this->additionalOrderInputFactory->create();
        $dateTime = $this->dateTimeFactory->create($order->getCreatedAt());

        $additionalInput->orderDate = $dateTime->format('YmdHis');
        $additionalInput->typeInformation = $this->typeInformationBuilder->create($order);

        return $additionalInput;
    }
}
