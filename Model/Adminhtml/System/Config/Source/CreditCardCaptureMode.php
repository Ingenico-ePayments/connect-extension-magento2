<?php

namespace Ingenico\Connect\Model\Adminhtml\System\Config\Source;

use Ingenico\Connect\Model\Config;

class CreditCardCaptureMode
{
    /**
     * @var string
     */
    private $direct = Config::CONFIG_INGENICO_CAPTURES_MODE_DIRECT;

    /**
     * @var string
     */
    private $authorize = Config::CONFIG_INGENICO_CAPTURES_MODE_AUTHORIZE;

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => $this->authorize,
                'label' => __('Captured')
            ],
            [
                'value' => $this->direct,
                'label' => __('Authorized')
            ],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array[]
     */
    public function toArray()
    {
        return [
            $this->authorize => __('Delayed Capture'),
            $this->direct => __('Direct Capture'),
        ];
    }
}
