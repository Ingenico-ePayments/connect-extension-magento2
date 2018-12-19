<?php
/**
 * See LICENSE.md for license details.
 */

namespace Netresearch\Epayments\Gateway;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;

class PaymentAction implements ValueHandlerInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * PaymentAction constructor.
     *
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Retrieve method configured value
     *
     * @param array $subject
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function handle(array $subject, $storeId = null)
    {
        $captureMode = $this->config->getCaptureMode($storeId);

        if ($captureMode === Config::CONFIG_INGENICO_CAPTURES_MODE_AUTHORIZE) {
            $captureMode = AbstractMethod::ACTION_AUTHORIZE;
        } else {
            $captureMode = AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
        }

        return $captureMode;
    }
}
