<?php

namespace Netresearch\Epayments\Model\Ingenico\Action;

class TestAccountAction extends AbstractAction implements ActionInterface
{
    const STATE_OK = 'OK';
    const STATE_FAIL = 'FAIL';

    /**
     * Invokes a request to Ingenico Connect for verifying account credentials
     *
     * @param $scopeId
     * @param string[] $data
     * @return string
     */
    public function process($scopeId, $data = [])
    {
        try {
            $this->ingenicoClient->ingenicoTestAccount($scopeId, $data);
            $status = self::STATE_OK;
        } catch (\Exception $exception) {
            $status = self::STATE_FAIL;
        }

        return $status;
    }
}
