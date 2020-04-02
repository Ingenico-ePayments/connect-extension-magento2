<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Refund;

use Ingenico\Connect\Helper\Data as DataHelper;
use Ingenico\Connect\Sdk\Domain\Refund\ApproveRefundRequestFactory;
use Ingenico\Connect\Sdk\Domain\Refund\ApproveRefundRequest;
use Magento\Sales\Api\Data\CreditmemoInterface;

class ApproveRefundRequestBuilder
{
    /**
     * @var ApproveRefundRequestFactory
     */
    private $approveRefundRequestFactory;

    public function __construct(ApproveRefundRequestFactory $approveRefundRequestFactory)
    {
        $this->approveRefundRequestFactory = $approveRefundRequestFactory;
    }

    /**
     * @param CreditmemoInterface $creditMemo
     * @return ApproveRefundRequest
     */
    public function build(CreditmemoInterface $creditMemo): ApproveRefundRequest
    {
        $amount = DataHelper::formatIngenicoAmount($creditMemo->getGrandTotal());
        $request = $this->approveRefundRequestFactory->create();
        $request->amount = $amount;

        return $request;
    }
}
