<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Checks;

use Ingenico\Connect\PaymentMethod\PaymentMethods;
use Magento\Payment\Model\Checks\SpecificationInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;

use function array_key_exists;
use function in_array;
use function strpos;

class IsAvailableInApi implements SpecificationInterface
{
    private $availableMethods = null;

    /**
     * @var PaymentMethods
     */
    private $paymentMethods;

    private $validPaymentMethods = [
        PaymentMethods::HOSTED,
        PaymentMethods::CARDS,
        PaymentMethods::CARDS_VAULT,
        PaymentMethods::AMERICAN_EXPRESS_VAULT,
        PaymentMethods::CARTE_BANCAIRE_VAULT,
        PaymentMethods::MASTERCARD_VAULT,
        PaymentMethods::VISA_VAULT,
    ];

    /**
     * @param PaymentMethods $paymentMethods
     */
    public function __construct(PaymentMethods $paymentMethods)
    {
        $this->paymentMethods = $paymentMethods;
    }

    public function isApplicable(MethodInterface $paymentMethod, Quote $quote)
    {
        $code = $paymentMethod->getCode();
        if (strpos($code, 'ingenico_') !== 0 || in_array($code, $this->validPaymentMethods)) {
            return true;
        }

        if ($this->availableMethods === null) {
            $this->availableMethods = $this->paymentMethods->getAvailableMethods($quote);
        }

        if ($this->availableMethods !== null) {
            return array_key_exists($code, $this->availableMethods);
        }

        return false;
    }
}
