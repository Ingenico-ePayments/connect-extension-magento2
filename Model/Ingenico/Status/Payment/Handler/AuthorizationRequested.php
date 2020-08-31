<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

/**
 * Class AuthorizationRequested
 *
 * @package Ingenico\Connect\Model\Ingenico\Status
 */
class AuthorizationRequested extends PendingFraudApproval
{
    protected const EVENT_STATUS = 'authorization_requested';

    /**
     * The only difference between the AUTHORIZATION_REQUESTED and the PENDING_FRAUD_APPROVAL status currently is
     * that AUTHORIZATION_REQUESTED can not be reviewed. This difference is handled in
     * Ingenico\Connect\Gateway\CanReviewPayment
     */
}
