<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Analytics.php';
require_once __DIR__ . '/../classes/Expense.php';
require_once __DIR__ . '/../includes/functions.php';


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Models
|--------------------------------------------------------------------------
*/

$analytics = new Analytics($pdo);
$expenseModel = new Expense($pdo);


/*
|--------------------------------------------------------------------------
| Summary Statistics
|--------------------------------------------------------------------------
*/

$totalExpense =
    $expenseModel->getTotalByUser($userId);

$currentMonth =
    $expenseModel->getCurrentMonthTotal($userId);

$expenseCount =
    $analytics->getExpenseCount($userId);

$averageExpense =
    $analytics->getAverageExpense($userId);

$highestExpense =
    $analytics->getHighestExpense($userId);

$currentMonthCount =
    $analytics->getCurrentMonthCount($userId);


/*
|--------------------------------------------------------------------------
| Chart Data
|--------------------------------------------------------------------------
*/

$categoryData =
    $analytics->getSpendingByCategory($userId);

$monthlyData =
    $analytics->getMonthlySpending(
        $userId,
        6
    );


/*
|--------------------------------------------------------------------------
| Prepare Safe JSON For JavaScript
|--------------------------------------------------------------------------
*/

$analyticsData = [
    'categories' => $categoryData,
    'monthly' => $monthlyData
];


$analyticsJson = json_encode(
    $analyticsData,
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_UNESCAPED_UNICODE
);


$analyticsJson = json_encode(
    $analyticsData,
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_UNESCAPED_UNICODE
);

if ($analyticsJson === false) {

    $analyticsJson =
        '{"categories":[],"monthly":[]}';
}


if ($analyticsJson === false) {
    $analyticsJson = '{"categories":[],"monthly":[]}';
}


/*
|--------------------------------------------------------------------------
| Page Layout
|--------------------------------------------------------------------------
*/

$pageTitle = 'Analytics';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="dashboard-main">

    <div class="content-card">


        <!-- Page Header -->

        <div class="dashboard-header">

            <div>

                <h1>
                    Expense Analytics
                </h1>

                <p>
                    Analyze your spending patterns and financial activity.
                </p>

            </div>

        </div>


        <!-- Summary Cards -->

        <div class="analytics-summary">


            <div class="stat-card">

                <h3>
                    Total Spending
                </h3>

                <p>
                    ₹<?= number_format(
                        $totalExpense,
                        2
                    ) ?>
                </p>

            </div>


            <div class="stat-card">

                <h3>
                    This Month
                </h3>

                <p>
                    ₹<?= number_format(
                        $currentMonth,
                        2
                    ) ?>
                </p>

            </div>


            <div class="stat-card">

                <h3>
                    Transactions
                </h3>

                <p>
                    <?= number_format(
                        $expenseCount
                    ) ?>
                </p>

            </div>


            <div class="stat-card">

                <h3>
                    Average Expense
                </h3>

                <p>
                    ₹<?= number_format(
                        $averageExpense,
                        2
                    ) ?>
                </p>

            </div>


            <div class="stat-card">

                <h3>
                    Highest Expense
                </h3>

                <p>
                    ₹<?= number_format(
                        $highestExpense,
                        2
                    ) ?>
                </p>

            </div>


            <div class="stat-card">

                <h3>
                    This Month's Transactions
                </h3>

                <p>
                    <?= number_format(
                        $currentMonthCount
                    ) ?>
                </p>

            </div>

        </div>


        <!-- Charts -->

        <div class="analytics-grid">


            <!-- Category Chart -->

            <div class="chart-card">

                <h2>
                    Spending by Category
                </h2>

                <div class="chart-container">

                    <canvas
                        id="categoryChart"
                    ></canvas>

                </div>

            </div>


            <!-- Monthly Chart -->

            <div class="chart-card">

                <h2>
                    Monthly Spending
                </h2>

                <div class="chart-container">

                    <canvas
                        id="monthlyChart"
                    ></canvas>

                </div>

            </div>

        </div>


        <!-- Empty Data Message -->

        <?php if (
            empty($categoryData) &&
            empty($monthlyData)
        ): ?>

            <div class="empty-state">

                <h3>
                    No analytics data available
                </h3>

                <p>
                    Add some expenses to see your spending
                    analytics and charts.
                </p>

                <a
                    href="add_expense.php"
                    class="button"
                >
                    + Add Expense
                </a>

            </div>

        <?php endif; ?>

    </div>

</main>


<!-- Analytics Data For JavaScript -->

<script>

    window.analyticsData =
        <?= $analyticsJson ?>;

</script>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>