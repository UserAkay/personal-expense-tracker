<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Expense.php';
require_once __DIR__ . '/../classes/Analytics.php';
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

$expenseModel =
    new Expense($pdo);

$analytics =
    new Analytics($pdo);


/*
|--------------------------------------------------------------------------
| Date Range
|--------------------------------------------------------------------------
*/

$endDate =
    date('Y-m-d');

$startDate =
    date(
        'Y-m-d',
        strtotime('-30 days')
    );


/*
|--------------------------------------------------------------------------
| Handle Date Filter
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $requestedStart =
        is_string($_GET['start_date'] ?? null)
            ? $_GET['start_date']
            : '';

    $requestedEnd =
        is_string($_GET['end_date'] ?? null)
            ? $_GET['end_date']
            : '';


    if (
        $requestedStart !== ''
        && DateTime::createFromFormat(
            'Y-m-d',
            $requestedStart
        ) !== false
    ) {

        $startDate =
            $requestedStart;
    }


    if (
        $requestedEnd !== ''
        && DateTime::createFromFormat(
            'Y-m-d',
            $requestedEnd
        ) !== false
    ) {

        $endDate =
            $requestedEnd;
    }
}


/*
|--------------------------------------------------------------------------
| Validate Date Range
|--------------------------------------------------------------------------
*/

if ($startDate > $endDate) {

    $temp =
        $startDate;

    $startDate =
        $endDate;

    $endDate =
        $temp;
}


/*
|--------------------------------------------------------------------------
| Get User Expenses
|--------------------------------------------------------------------------
*/

$allExpenses =
    $expenseModel->getAllByUser(
        $userId
    );


/*
|--------------------------------------------------------------------------
| Filter Expenses By Date
|--------------------------------------------------------------------------
*/

$expenses = [];


foreach ($allExpenses as $expense) {

    $expenseDate =
        (string) (
            $expense['expense_date']
            ?? ''
        );


    if (
        $expenseDate >= $startDate
        && $expenseDate <= $endDate
    ) {

        $expenses[] =
            $expense;
    }
}


/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

$total =
    0.0;

$count =
    count($expenses);

$highest =
    0.0;


foreach ($expenses as $expense) {

    $amount =
        (float) (
            $expense['amount']
            ?? 0
        );


    $total +=
        $amount;


    if ($amount > $highest) {

        $highest =
            $amount;
    }
}


$average =
    $count > 0
        ? $total / $count
        : 0.0;


/*
|--------------------------------------------------------------------------
| Category Breakdown
|--------------------------------------------------------------------------
*/

$categoryTotals = [];


foreach ($expenses as $expense) {

    $category =
        (string) (
            $expense['category_name']
            ?? 'Uncategorized'
        );


    $amount =
        (float) (
            $expense['amount']
            ?? 0
        );


    if (
        !isset(
            $categoryTotals[$category]
        )
    ) {

        $categoryTotals[$category] =
            0.0;
    }


    $categoryTotals[$category] +=
        $amount;
}


arsort(
    $categoryTotals
);


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$pageTitle = 'Reports';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>


<main class="dashboard-main">

    <div class="content-card">


        <div class="dashboard-header">

            <div>

                <h1>
                    Expense Reports
                </h1>

                <p>
                    Review your spending for a selected period.
                </p>

            </div>

        </div>


        <!-- Date Filter -->

        <div class="dashboard-section">

            <h2>
                Report Period
            </h2>


            <form method="GET">

                <div
                    style="
                        display:flex;
                        gap:15px;
                        flex-wrap:wrap;
                        align-items:end;
                    "
                >

                    <div class="form-group">

                        <label for="start_date">
                            Start Date
                        </label>

                        <input
                            type="date"
                            id="start_date"
                            name="start_date"
                            value="<?= e($startDate) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="end_date">
                            End Date
                        </label>

                        <input
                            type="date"
                            id="end_date"
                            name="end_date"
                            value="<?= e($endDate) ?>"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="button"
                    >
                        Generate Report
                    </button>

                </div>

            </form>

        </div>


        <!-- Summary -->

        <div class="analytics-summary">


            <div class="stat-card">

                <h3>
                    Total Spending
                </h3>

                <p>
                    ₹<?= number_format(
                        $total,
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
                        $count
                    ) ?>
                </p>

            </div>


            <div class="stat-card">

                <h3>
                    Average Expense
                </h3>

                <p>
                    ₹<?= number_format(
                        $average,
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
                        $highest,
                        2
                    ) ?>
                </p>

            </div>


        </div>


        <!-- Category Breakdown -->

        <div class="dashboard-section">

            <h2>
                Spending By Category
            </h2>


            <?php if (empty($categoryTotals)): ?>

                <div class="empty-state">

                    <h3>
                        No expenses found
                    </h3>

                    <p>
                        There are no expenses in the selected period.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Amount
                                </th>

                                <th>
                                    Percentage
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $categoryTotals
                                as $category =>
                                $amount
                            ): ?>

                                <?php

                                $percentage =
                                    $total > 0
                                        ? (
                                            $amount
                                            / $total
                                        ) * 100
                                        : 0;

                                ?>

                                <tr>

                                    <td>
                                        <?= e($category) ?>
                                    </td>

                                    <td>
                                        ₹<?= number_format(
                                            $amount,
                                            2
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $percentage,
                                            1
                                        ) ?>%
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>


        <!-- Expense Details -->

        <div class="dashboard-section">

            <h2>
                Expense Details
            </h2>


            <?php if (empty($expenses)): ?>

                <div class="empty-state">

                    <p>
                        No expenses found for this period.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Description
                                </th>

                                <th>
                                    Amount
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $expenses
                                as $expense
                            ): ?>

                                <tr>

                                    <td>
                                        <?= e(
                                            $expense['expense_date']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= e(
                                            $expense['category_name']
                                            ?? 'Uncategorized'
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= e(
                                            $expense['description']
                                            ?? ''
                                        ) ?>
                                    </td>


                                    <td>
                                        ₹<?= number_format(
                                            (float) $expense['amount'],
                                            2
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>


    </div>

</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>