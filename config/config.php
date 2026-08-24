<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| Application Configuration
|--------------------------------------------------------------------------
*/

define(
    'APP_NAME',
    'Personal Expense Tracker'
);


/*
|--------------------------------------------------------------------------
| Timezone
|--------------------------------------------------------------------------
*/

define(
    'APP_TIMEZONE',
    'Asia/Kolkata'
);

date_default_timezone_set(APP_TIMEZONE);


/*
|--------------------------------------------------------------------------
| Application Paths
|--------------------------------------------------------------------------
*/

define(
    'BASE_PATH',
    dirname(__DIR__)
);


/*
|--------------------------------------------------------------------------
| Receipt Upload Configuration
|--------------------------------------------------------------------------
|
| These settings are ready for a future receipt-upload feature.
|
*/

define(
    'UPLOAD_PATH',
    BASE_PATH . '/uploads/receipts/'
);

define(
    'MAX_RECEIPT_SIZE',
    5 * 1024 * 1024
);

define(
    'ALLOWED_RECEIPT_TYPES',
    [
        'image/jpeg',
        'image/png',
        'application/pdf'
    ]
);