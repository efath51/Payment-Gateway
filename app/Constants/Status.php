<?php

namespace App\Constants;

class Status
{
    // Payment status
    const PAYMENT_INITIATE = 0;
    const PAYMENT_SUCCESS = 1;
    const PAYMENT_PENDING = 2;
    const PAYMENT_REJECT = 3;

    // Gateway type
    const AUTOMATIC_GATEWAY_LIMIT = 1000;

    // Other statuses
    const ENABLE = 1;
    const DISABLE = 0;
}
