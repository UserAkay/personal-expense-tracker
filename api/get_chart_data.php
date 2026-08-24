<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Analytics.php';


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
| Analytics Model
|--------------------------------------------------------------------------
*/

$analytics = new Analytics($pdo);


/*
|--------------------------------------------------------------------------
| Get Analytics Data
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | Spending By Category
    |--------------------------------------------------------------------------
    */

    $categoryData =
        $analytics->getSpendingByCategory(
            $userId
        );


    /*
    |--------------------------------------------------------------------------
    | Monthly Spending
    |--------------------------------------------------------------------------
    */

    $monthlyData =
        $analytics->getMonthlySpending(
            $userId,
            6
        );


    /*
    |--------------------------------------------------------------------------
    | Category Chart
    |--------------------------------------------------------------------------
    */

    $categories = [];
    $categoryTotals = [];

    foreach ($categoryData as $row) {

        $categories[] =
            (string) (
                $row['category_name']
                ?? 'Uncategorized'
            );

        $categoryTotals[] =
            (float) (
                $row['total']
                ?? 0
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Monthly Chart
    |--------------------------------------------------------------------------
    */

    $months = [];
    $monthlyTotals = [];

    foreach ($monthlyData as $row) {

        $months[] =
            (string) (
                $row['month']
                ?? ''
            );

        $monthlyTotals[] =
            (float) (
                $row['total']
                ?? 0
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    $response = [
        'success' => true,

        'category' => [
            'labels' => $categories,
            'data' => $categoryTotals
        ],

        'monthly' => [
            'labels' => $months,
            'data' => $monthlyTotals
        ]
    ];


    $json = json_encode(
        $response,
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_UNESCAPED_UNICODE |
        JSON_THROW_ON_ERROR
    );


    echo $json;


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Log Internal Error
    |--------------------------------------------------------------------------
    */

    error_log(
        'Chart API error: ' .
        $e->getMessage()
    );


    /*
    |--------------------------------------------------------------------------
    | Generic Response
    |--------------------------------------------------------------------------
    */

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to load analytics data.'
    ]);
}