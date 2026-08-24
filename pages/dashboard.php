<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Expense.php';
require_once __DIR__ . '/../classes/Analytics.php';
require_once __DIR__ . '/../classes/Budget.php';
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

$expenseModel = new Expense($pdo);
$analytics = new Analytics($pdo);
$budgetModel = new Budget($pdo);


/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

$totalExpenses = $expenseModel->getTotalByUser(
    $userId
);

$currentMonth = $expenseModel->getCurrentMonthTotal(
    $userId
);

$expenseCount = $analytics->getExpenseCount(
    $userId
);

$averageExpense = $analytics->getAverageExpense(
    $userId
);


/*
|--------------------------------------------------------------------------
| Current Month Budget
|--------------------------------------------------------------------------
*/

$currentBudgetMonth = date('Y-m');

$currentBudgets = $budgetModel->getAllByUser(
    $userId,
    $currentBudgetMonth
);


/*
|--------------------------------------------------------------------------
| Recent Expenses
|--------------------------------------------------------------------------
*/

$allExpenses = $expenseModel->getAllByUser(
    $userId
);

$recentExpenses = array_slice(
    $allExpenses,
    0,
    5
);


/*
|--------------------------------------------------------------------------
| Page Layout
|--------------------------------------------------------------------------
*/

$pageTitle = 'Dashboard';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>


<main class="dashboard-main">

    <div class="content-card">


        <!-- =========================================================
             PAGE HEADER
        ========================================================== -->

        <div class="dashboard-header">

            <div>

                <h1>
                    Dashboard
                </h1>

                <p>
                    Welcome back,
                    <?= e($_SESSION['user_name'] ?? 'User') ?>.
                    Here's an overview of your expenses.
                </p>

            </div>


            <div>

                <a
                    href="add_expense.php"
                    class="button"
                >
                    + Add Expense
                </a>

            </div>

        </div>



        <!-- =========================================================
             STATISTICS
        ========================================================== -->

        <div class="analytics-summary">


            <!-- Total Spending -->

            <div class="stat-card">

                <h3>
                    Total Spending
                </h3>

                <p>
                    ₹<?= number_format(
                        $totalExpenses,
                        2
                    ) ?>
                </p>

            </div>


            <!-- Current Month -->

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


            <!-- Transactions -->

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


            <!-- Average Expense -->

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

        </div>



        <!-- =========================================================
             QUICK ACTIONS
        ========================================================== -->

        <div class="dashboard-section">

            <h2>
                Quick Actions
            </h2>


            <div class="quick-actions">


                <!-- Add Expense -->

                <a
                    href="add_expense.php"
                    class="quick-action"
                >

                    <strong>
                        + Add Expense
                    </strong>

                    <span>
                        Record a new expense
                    </span>

                </a>


                <!-- View Expenses -->

                <a
                    href="expense.php"
                    class="quick-action"
                >

                    <strong>
                        View Expenses
                    </strong>

                    <span>
                        Manage your transactions
                    </span>

                </a>


                <!-- Analytics -->

                <a
                    href="analytics.php"
                    class="quick-action"
                >

                    <strong>
                        View Analytics
                    </strong>

                    <span>
                        Analyze your spending
                    </span>

                </a>


                <!-- Categories -->

                <a
                    href="categories.php"
                    class="quick-action"
                >

                    <strong>
                        Categories
                    </strong>

                    <span>
                        Manage expense categories
                    </span>

                </a>


                <!-- Budgets -->

                <a
                    href="budgets.php?month=<?= e($currentBudgetMonth) ?>"
                    class="quick-action"
                >

                    <strong>
                        Budgets
                    </strong>

                    <span>
                        Manage your monthly budgets
                    </span>

                </a>

            </div>

        </div>



        <!-- =========================================================
             RECENT EXPENSES
        ========================================================== -->

        <div class="dashboard-section">


            <div class="section-header">

                <div>

                    <h2>
                        Recent Expenses
                    </h2>

                    <p>
                        Your latest transactions.
                    </p>

                </div>


                <a href="expense.php">
                    View All
                </a>

            </div>



            <?php if (empty($recentExpenses)): ?>


                <div class="empty-state">

                    <h3>
                        No Expenses Yet
                    </h3>

                    <p>
                        Start tracking your spending by
                        adding your first expense.
                    </p>

                    <a
                        href="add_expense.php"
                        class="button"
                    >
                        + Add Your First Expense
                    </a>

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

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>



                        <tbody>


                            <?php foreach (
                                $recentExpenses
                                as $expense
                            ): ?>


                                <tr>


                                    <!-- Date -->

                                    <td>

                                        <?= e(
                                            $expense['expense_date']
                                        ) ?>

                                    </td>


                                    <!-- Category -->

                                    <td>

                                        <?= e(
                                            $expense['category_name']
                                            ?? 'Uncategorized'
                                        ) ?>

                                    </td>


                                    <!-- Description -->

                                    <td>

                                        <?= e(
                                            $expense['description']
                                            ?? ''
                                        ) ?>

                                    </td>


                                    <!-- Amount -->

                                    <td>

                                        ₹<?= number_format(
                                            (float) $expense['amount'],
                                            2
                                        ) ?>

                                    </td>


                                    <!-- Action -->

                                    <td>

                                        <a
                                            href="edit_expense.php?id=<?= (int) $expense['id'] ?>"
                                        >
                                            Edit
                                        </a>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php endif; ?>


        </div>



        <!-- =========================================================
             BUDGET OVERVIEW
        ========================================================== -->

        <div class="dashboard-section">


            <div class="section-header">


                <div>

                    <h2>
                        Budget Overview
                    </h2>

                    <p>
                        Track your spending against your
                        monthly budgets.
                    </p>

                </div>


                <a
                    href="budgets.php?month=<?= e($currentBudgetMonth) ?>"
                >
                    View All Budgets
                </a>


            </div>



            <?php if (empty($currentBudgets)): ?>


                <!-- No Budgets -->

                <div class="empty-state">

                    <h3>
                        No Budgets for This Month
                    </h3>

                    <p>
                        Create a monthly budget to start
                        tracking your spending.
                    </p>

                    <a
                        href="add_budget.php"
                        class="button"
                    >
                        + Add Budget
                    </a>

                </div>


            <?php else: ?>


                <!-- Budget Cards -->

                <div class="budget-grid">


                    <?php foreach (
                        $currentBudgets
                        as $budget
                    ): ?>


                        <?php

                        $budgetAmount =
                            (float) $budget['amount'];

                        $spentAmount =
                            (float) $budget['spent'];

                        $remainingAmount =
                            $budgetAmount - $spentAmount;


                        /*
                        |--------------------------------------------------------------------------
                        | Calculate Percentage
                        |--------------------------------------------------------------------------
                        */

                        if ($budgetAmount > 0) {

                            $percentage =
                                ($spentAmount / $budgetAmount) * 100;

                        } else {

                            $percentage = 0;

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Progress Bar Percentage
                        |--------------------------------------------------------------------------
                        |
                        | Prevent the progress bar from becoming
                        | wider than 100%.
                        |
                        */

                        $displayPercentage =
                            min(
                                max(
                                    $percentage,
                                    0
                                ),
                                100
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Budget Status
                        |--------------------------------------------------------------------------
                        */

                        if ($percentage >= 100) {

                            $status =
                                'Over Budget';

                        } elseif ($percentage >= 80) {

                            $status =
                                'Almost Used';

                        } else {

                            $status =
                                'On Track';

                        }

                        ?>


                        <div class="budget-card">


                            <!-- Budget Header -->

                            <div class="budget-card-header">

                                <h2>

                                    <?= e(
                                        $budget['category_name']
                                    ) ?>

                                </h2>


                                <span>

                                    <?= e(
                                        $status
                                    ) ?>

                                </span>

                            </div>



                            <!-- Budget Values -->

                            <div class="budget-values">


                                <!-- Budget -->

                                <div>

                                    <small>
                                        Budget
                                    </small>

                                    <strong>

                                        ₹<?= number_format(
                                            $budgetAmount,
                                            2
                                        ) ?>

                                    </strong>

                                </div>


                                <!-- Spent -->

                                <div>

                                    <small>
                                        Spent
                                    </small>

                                    <strong>

                                        ₹<?= number_format(
                                            $spentAmount,
                                            2
                                        ) ?>

                                    </strong>

                                </div>


                                <!-- Remaining -->

                                <div>

                                    <small>
                                        Remaining
                                    </small>

                                    <strong>

                                        ₹<?= number_format(
                                            $remainingAmount,
                                            2
                                        ) ?>

                                    </strong>

                                </div>


                            </div>



                            <!-- Progress Bar -->

                            <div class="budget-progress">

                                <div
                                    class="budget-progress-bar"
                                    style="width: <?= e(
                                        (string) $displayPercentage
                                    ) ?>%;"
                                ></div>

                            </div>



                            <!-- Percentage -->

                            <p>

                                <?= number_format(
                                    $percentage,
                                    1
                                ) ?>% used

                            </p>



                            <!-- Budget Actions -->

                            <div class="budget-actions">


                                <a
                                    href="edit_budget.php?id=<?= (int) $budget['id'] ?>"
                                >
                                    Edit
                                </a>


                                |


                                <a
                                    href="budgets.php?month=<?= e(
                                        $currentBudgetMonth
                                    ) ?>"
                                >
                                    Manage
                                </a>


                            </div>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </div>



        <!-- =========================================================
             ANALYTICS PREVIEW
        ========================================================== -->

        <div class="dashboard-section">


            <div class="section-header">


                <div>

                    <h2>
                        Spending Overview
                    </h2>

                    <p>
                        Visual overview of your spending.
                    </p>

                </div>


                <a href="analytics.php">
                    Full Analytics
                </a>


            </div>



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


        </div>


    </div>

</main>



<?php

require_once __DIR__ . '/../includes/footer.php';

?>