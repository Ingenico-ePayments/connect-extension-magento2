<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

/**
 * Class AuthorizationRequested
 *
 * @package Worldline\Connect\Model\Worldline\Status
 */
class AuthorizationRequested extends PendingFraudApproval
{
    protected const EVENT_STATUS = 'authorization_requested';

    /**
     * The only difference between the AUTHORIZATION_REQUESTED and the PENDING_FRAUD_APPROVAL status currently is
     * that AUTHORIZATION_REQUESTED can not be reviewed. This difference is handled in
     * Worldline\Connect\Gateway\CanReviewPayment
     */
}
