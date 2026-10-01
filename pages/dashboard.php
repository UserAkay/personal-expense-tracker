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

$userName = $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? 'User';


/*
|--------------------------------------------------------------------------
| Models
|--------------------------------------------------------------------------
*/

$expenseModel = new Expense($pdo);
$analyticsModel = new Analytics($pdo);
$budgetModel = new Budget($pdo);


/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

$totalSpending =
    $expenseModel->getTotalByUser($userId);

$thisMonth =
    $expenseModel->getCurrentMonthTotal($userId);

$allExpenses =
    $expenseModel->getAllByUser($userId);

$transactionCount =
    count($allExpenses);

$averageExpense =
    $transactionCount > 0
        ? $totalSpending / $transactionCount
        : 0;


/*
|--------------------------------------------------------------------------
| Recent Expenses
|--------------------------------------------------------------------------
*/

$recentExpenses =
    array_slice($allExpenses, 0, 5);


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$pageTitle = 'Dashboard';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="dashboard-main">

    <div class="content-card">

        <!-- Dashboard Header -->

        <div class="dashboard-header">

            <div>

                <span class="page-eyebrow">
                    OVERVIEW
                </span>

                <h1>
                    Dashboard
                </h1>

                <p>
                    Welcome back,
                    <strong><?= e($userName) ?></strong>.
                    Here's your financial overview.
                </p>

            </div>

            <a
                href="add_expense.php"
                class="button"
            >
                + Add Expense
            </a>

        </div>


        <!-- Statistics -->

        <div class="analytics-summary">

            <div class="stat-card">

                <h3>
                    Total Spending
                </h3>

                <p>
                    ₹<?= number_format(
                        $totalSpending,
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
                        $thisMonth,
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
                        $transactionCount
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

        </div>


        <!-- Quick Actions -->

        <div class="dashboard-section">

            <div class="section-header">

                <div>

                    <h2>
                        Quick Actions
                    </h2>

                    <p>
                        Manage your finances quickly.
                    </p>

                </div>

            </div>


            <div class="quick-actions">

                <a
                    href="add_expense.php"
                    class="quick-action"
                >

                    <strong>
                        + Add Expense
                    </strong>

                    <span>
                        Record a new transaction
                    </span>

                </a>


                <a
                    href="expense.php"
                    class="quick-action"
                >

                    <strong>
                        View Expenses
                    </strong>

                    <span>
                        Browse your transactions
                    </span>

                </a>


                <a
                    href="budgets.php"
                    class="quick-action"
                >

                    <strong>
                        Manage Budgets
                    </strong>

                    <span>
                        Track your spending limits
                    </span>

                </a>


                <a
                    href="analytics.php"
                    class="quick-action"
                >

                    <strong>
                        View Analytics
                    </strong>

                    <span>
                        Explore spending insights
                    </span>

                </a>

            </div>

        </div>

<!-- Recent Transactions -->

        <div class="dashboard-section">

            <div class="section-header">

                <div>

                    <h2>
                        Recent Transactions
                    </h2>

                    <p>
                        Your latest recorded expenses.
                    </p>

                </div>

                <a href="expenses.php">
                    View All
                </a>

            </div>


            <?php if (empty($recentExpenses)): ?>

                <div class="empty-state">

                    <div class="empty-state-icon">
                        +
                    </div>

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
                        Add Your First Expense
                    </a>

                </div>

            <?php else: ?>

                <div class="expense-table-wrapper">

                    <table class="expense-table">

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

                                    <td class="expense-date">

                                        <?= e(
                                            $expense[
                                                'expense_date'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <span
                                            class="category-badge"
                                        >
                                            <?= e(
                                                $expense[
                                                    'category_name'
                                                ]
                                                ??
                                                'Uncategorized'
                                            ) ?>
                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="expense-description"
                                            title="<?= e(
                                                $expense[
                                                    'description'
                                                ] ?? ''
                                            ) ?>"
                                        >
                                            <?= e(
                                                $expense[
                                                    'description'
                                                ] ??
                                                'No description'
                                            ) ?>
                                        </span>

                                    </td>


                                    <td>

                                        <strong
                                            class="expense-amount"
                                        >
                                            ₹<?= number_format(
                                                (float)
                                                $expense['amount'],
                                                2
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <div
                                            class="expense-actions"
                                        >

                                            <a
                                                href="edit_expense.php?id=<?= (int) $expense['id'] ?>"
                                                class="action-edit"
                                            >
                                                Edit
                                            </a>

                                            <a
                                                href="delete_expense.php?id=<?= (int) $expense['id'] ?>"
                                                class="action-delete"
                                            >
                                                Delete
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>


        <!-- Analytics Preview -->

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
        
<!-- Footer spacing -->

        <div class="dashboard-bottom-space"></div>

    </div>

</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>