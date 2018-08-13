<?php

namespace Netresearch\Epayments\Model\Adminhtml\System\Config\Source;

use Netresearch\Epayments\Model\Config;

class CaptureMode
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
                'label' => __('Delayed Settlement')
            ],
            [
                'value' => $this->direct,
                'label' => __('Direct Capture')
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
            $this->authorize => __('Delayed Settlement'),
            $this->direct => __('Direct Capture'),
        ];
    }
}
