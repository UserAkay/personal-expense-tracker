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
| Response Headers
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');


/*
|--------------------------------------------------------------------------
| Allow GET Only
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    header('Allow: GET');

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
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
| Get Summary
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | Total Spending
    |--------------------------------------------------------------------------
    */

    $total =
        $expenseModel->getTotalByUser(
            $userId
        );


    /*
    |--------------------------------------------------------------------------
    | Current Month Spending
    |--------------------------------------------------------------------------
    */

    $currentMonth =
        $expenseModel->getCurrentMonthTotal(
            $userId
        );


    /*
    |--------------------------------------------------------------------------
    | All User Expenses
    |--------------------------------------------------------------------------
    */

    $expenses =
        $expenseModel->getAllByUser(
            $userId
        );


    /*
    |--------------------------------------------------------------------------
    | Transaction Count
    |--------------------------------------------------------------------------
    */

    $transactionCount =
        count($expenses);


    /*
    |--------------------------------------------------------------------------
    | Average Expense
    |--------------------------------------------------------------------------
    */

    $average =
        $transactionCount > 0
            ? $total / $transactionCount
            : 0.0;


    /*
    |--------------------------------------------------------------------------
    | Highest Expense
    |--------------------------------------------------------------------------
    */

    $highest = 0.0;

    foreach ($expenses as $expense) {

        $amount =
            (float) (
                $expense['amount']
                ?? 0
            );

        if ($amount > $highest) {
            $highest = $amount;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Current Month Transaction Count
    |--------------------------------------------------------------------------
    */

    $currentMonthCount = 0;

    $currentMonthKey =
        date('Y-m');

    foreach ($expenses as $expense) {

        $expenseDate =
            (string) (
                $expense['expense_date']
                ?? ''
            );

        if (
            substr(
                $expenseDate,
                0,
                7
            ) === $currentMonthKey
        ) {

            $currentMonthCount++;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    echo json_encode(
        [
            'success' => true,

            'summary' => [
                'total' => round(
                    $total,
                    2
                ),

                'current_month' => round(
                    $currentMonth,
                    2
                ),

                'transaction_count' =>
                    $transactionCount,

                'current_month_count' =>
                    $currentMonthCount,

                'average' => round(
                    $average,
                    2
                ),

                'highest' => round(
                    $highest,
                    2
                )
            ]
        ],

        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_UNESCAPED_UNICODE
    );


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Log Internal Error
    |--------------------------------------------------------------------------
    */

    error_log(
        'Expense summary API error: ' .
        $e->getMessage()
    );


    /*
    |--------------------------------------------------------------------------
    | Generic Error
    |--------------------------------------------------------------------------
    */

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to load expense summary.'
    ]);
}