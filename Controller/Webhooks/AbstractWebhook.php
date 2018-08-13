<?php

namespace Netresearch\Epayments\Controller\Webhooks;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

abstract class AbstractWebhook extends Action
{
    abstract public function execute();

    /**
     * Checks the headers of the request for a special endpoint verification
     *
     * @return ResultInterface|false
     */
    protected function checkVerification()
    {
        $verificationString = $this->getRequest()->getHeader('X-GCS-Webhooks-Endpoint-Verification');
        if ($verificationString) {
            $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $response->setHeader('Content-type', 'text/plain');
            $response->setContents($verificationString);

            return $response;
        }

        return false;
    }
}
