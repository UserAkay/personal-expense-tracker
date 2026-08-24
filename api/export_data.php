<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Expense.php';


/*
|--------------------------------------------------------------------------
| Allow GET Only
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    header('Allow: GET');

    exit(
        'Method Not Allowed'
    );
}


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Expense Model
|--------------------------------------------------------------------------
*/

$expenseModel = new Expense($pdo);


/*
|--------------------------------------------------------------------------
| Get User Expenses
|--------------------------------------------------------------------------
*/

try {

    $expenses =
        $expenseModel->getAllByUser(
            $userId
        );


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Log Internal Error
    |--------------------------------------------------------------------------
    */

    error_log(
        'Expense export error: ' .
        $e->getMessage()
    );


    http_response_code(500);

    exit(
        'Unable to export expenses.'
    );
}


/*
|--------------------------------------------------------------------------
| CSV Download Headers
|--------------------------------------------------------------------------
*/

$filename =
    'expenses-' .
    date('Y-m-d-H-i-s') .
    '.csv';


header(
    'Content-Type: text/csv; charset=UTF-8'
);

header(
    'Content-Disposition: attachment; filename="' .
    $filename .
    '"'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


/*
|--------------------------------------------------------------------------
| Open Output Stream
|--------------------------------------------------------------------------
*/

$output = fopen(
    'php://output',
    'wb'
);


if ($output === false) {

    http_response_code(500);

    exit(
        'Unable to create export.'
    );
}


/*
|--------------------------------------------------------------------------
| UTF-8 BOM
|--------------------------------------------------------------------------
|
| Helps spreadsheet applications such as Excel
| correctly detect UTF-8 CSV files.
|
*/

fwrite(
    $output,
    "\xEF\xBB\xBF"
);


/*
|--------------------------------------------------------------------------
| CSV Header
|--------------------------------------------------------------------------
*/

fputcsv(
    $output,
    [
        'ID',
        'Date',
        'Category',
        'Description',
        'Amount'
    ]
);


/*
|--------------------------------------------------------------------------
| CSV Rows
|--------------------------------------------------------------------------
*/

foreach ($expenses as $expense) {

    /*
    |--------------------------------------------------------------------------
    | Prevent Spreadsheet Formula Injection
    |--------------------------------------------------------------------------
    |
    | User-controlled text beginning with =, +, -, or @
    | can be interpreted as a spreadsheet formula.
    |
    */

    $description =
        (string) (
            $expense['description']
            ?? ''
        );

    if (
        $description !== '' &&
        in_array(
            $description[0],
            ['=', '+', '-', '@'],
            true
        )
    ) {

        $description =
            "'" . $description;
    }


    $category =
        (string) (
            $expense['category_name']
            ?? 'Uncategorized'
        );


    if (
        $category !== '' &&
        in_array(
            $category[0],
            ['=', '+', '-', '@'],
            true
        )
    ) {

        $category =
            "'" . $category;
    }


    fputcsv(
        $output,
        [
            (int) $expense['id'],

            (string) (
                $expense['expense_date']
                ?? ''
            ),

            $category,

            $description,

            number_format(
                (float) (
                    $expense['amount']
                    ?? 0
                ),
                2,
                '.',
                ''
            )
        ]
    );
}


/*
|--------------------------------------------------------------------------
| Close Stream
|--------------------------------------------------------------------------
*/

fclose($output);

exit;