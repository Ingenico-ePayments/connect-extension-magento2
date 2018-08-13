<?php

namespace Netresearch\Epayments\Model\Ingenico\Status;

/**
 * Class AuthorizationRequested
 *
 * @package Netresearch\Epayments\Model\Ingenico\Status
 */
class AuthorizationRequested extends PendingFraudApproval
{
    /**
     * The only difference between the AUTHORIZATION_REQUESTED and the PENDING_FRAUD_APPROVAL status currently is
     * that AUTHORIZATION_REQUESTED can not be reviewed. This difference is handled in
     * Netresearch\Epayments\Gateway\CanReviewPayment
     */
}
