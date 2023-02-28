<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\RequestBuilder\Refund;

use Ingenico\Connect\Sdk\Domain\Refund\ApproveRefundRequest;
use Ingenico\Connect\Sdk\Domain\Refund\ApproveRefundRequestFactory;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Worldline\Connect\Helper\Data as DataHelper;

class ApproveRefundRequestBuilder
{
    /**
     * @var ApproveRefundRequestFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
        $amount = DataHelper::formatWorldlineAmount($creditMemo->getGrandTotal());
        $request = $this->approveRefundRequestFactory->create();
        $request->amount = $amount;

        return $request;
    }
}
