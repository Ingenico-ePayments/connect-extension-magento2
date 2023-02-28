<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AdditionalOrderInput;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AdditionalOrderInputFactory;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\AdditionalInput\TypeInformationBuilder;

class AdditionalInputBuilder
{
    /**
     * @var AdditionalOrderInputFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $additionalOrderInputFactory;

    /**
     * @var DateTimeFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $dateTimeFactory;

    /**
     * @var TypeInformationBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
        $dateTime = $this->dateTimeFactory->create($order->getCreatedAt() ?? '');

        $additionalInput->orderDate = $dateTime->format('YmdHis');
        $additionalInput->typeInformation = $this->typeInformationBuilder->create($order);

        return $additionalInput;
    }
}
