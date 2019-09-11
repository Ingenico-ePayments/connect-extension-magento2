<?php

namespace Ingenico\Connect\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Ingenico\Connect\Model\ConfigInterface;

/**
 * Class Availability
 *
 * @package Ingenico\Connect\Gateway
 */
class Availability extends AbstractValidator
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Availability constructor.
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param ConfigInterface $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ConfigInterface $config
    ) {
        $this->config = $config;
        parent::__construct($resultFactory);
    }

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $validationSubject['payment']->getPayment();

        $isValid = $this->config->isActive($payment->getQuote()->getStoreId());

        return $this->createResult($isValid);
    }
}
