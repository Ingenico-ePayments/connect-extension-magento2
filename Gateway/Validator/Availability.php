<?php

namespace Netresearch\Epayments\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Netresearch\Epayments\Model\ConfigInterface;

class Availability extends AbstractValidator
{
    /**
     * @var ConfigInterface
     */
    private $config;

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
