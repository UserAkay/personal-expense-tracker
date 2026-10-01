<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Expense.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) $_SESSION['user_id'];

$expenseModel = new Expense($pdo);
$categoryModel = new Category($pdo);

$categories = $categoryModel->getAllByUser($userId);

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$selectedCategory = filter_input(
    INPUT_GET,
    'category_id',
    FILTER_VALIDATE_INT
);

if (
    $selectedCategory === false ||
    $selectedCategory === null ||
    $selectedCategory <= 0
) {
    $selectedCategory = null;
}

$selectedMonth = trim($_GET['month'] ?? '');

if (
    $selectedMonth !== '' &&
    !preg_match('/^\d{4}-\d{2}$/', $selectedMonth)
) {
    $selectedMonth = '';
}

/*
|--------------------------------------------------------------------------
| Load Expenses
|--------------------------------------------------------------------------
*/

$expenses = $expenseModel->getFilteredByUser(
    $userId,
    $selectedCategory,
    $selectedMonth !== '' ? $selectedMonth : null
);

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

$filteredTotal = 0.0;

foreach ($expenses as $expense) {
    $filteredTotal += (float) $expense['amount'];
}

$pageTitle = 'Expenses';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="dashboard-main">

    <div class="content-card expense-page">

        <?php if (!empty($_SESSION['success'])): ?>

            <div class="success">
                <?= e($_SESSION['success']) ?>
            </div>

            <?php unset($_SESSION['success']); ?>

        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>

            <div class="error">
                <?= e($_SESSION['error']) ?>
            </div>

            <?php unset($_SESSION['error']); ?>

        <?php endif; ?>

        <!-- Header -->

        <div class="expense-header">

            <div>
                <span class="page-eyebrow">
                    MONEY MANAGEMENT
                </span>

                <h1>Expenses</h1>

                <p>
                    Track, filter and manage all your expenses.
                </p>
            </div>

            <a
                href="add_expense.php"
                class="button"
            >
                + Add Expense
            </a>

        </div>

        <!-- Filters -->

        <div class="expense-filter-card">

            <div class="filter-title">
                <div>
                    <h2>Filter Expenses</h2>

                    <p>
                        Narrow down your transactions.
                    </p>
                </div>
            </div>

            <form
                method="GET"
                class="expense-filters"
            >

                <div class="filter-group">

                    <label for="category_id">
                        Category
                    </label>

                    <select
                        id="category_id"
                        name="category_id"
                    >

                        <option value="">
                            All Categories
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= (int) $category['id'] ?>"
                                <?= $selectedCategory === (int) $category['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($category['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="filter-group">

                    <label for="month">
                        Month
                    </label>

                    <input
                        type="month"
                        id="month"
                        name="month"
                        value="<?= e($selectedMonth) ?>"
                    >

                </div>

                <div class="filter-actions">

                    <button
                        type="submit"
                        class="button"
                    >
                        Apply Filters
                    </button>

                    <a
                        href="expense.php"
                        class="button button-secondary"
                    >
                        Clear
                    </a>

                </div>

            </form>

        </div>

        <!-- Summary -->

        <div class="expense-summary">

            <div class="expense-summary-card">

                <div class="summary-icon">
                    ₹
                </div>

                <div>
                    <span>
                        Filtered Spending
                    </span>

                    <strong>
                        ₹<?= number_format($filteredTotal, 2) ?>
                    </strong>
                </div>

            </div>

            <div class="expense-summary-card">

                <div class="summary-icon">
                    #
                </div>

                <div>
                    <span>
                        Transactions
                    </span>

                    <strong>
                        <?= number_format(count($expenses)) ?>
                    </strong>
                </div>

            </div>

        </div>

<!-- Expense List -->

        <div class="expense-list-section">

            <div class="section-header">

                <div>
                    <span class="page-eyebrow">
                        TRANSACTIONS
                    </span>

                    <h2>
                        Your Expenses
                    </h2>

                    <p>
                        <?= count($expenses) ?>
                        transaction<?= count($expenses) === 1 ? '' : 's' ?>
                        found.
                    </p>
                </div>

            </div>

            <?php if (empty($expenses)): ?>

                <div class="empty-state">

                    <div class="empty-state-icon">
                        ₹
                    </div>

                    <h3>
                        No Expenses Found
                    </h3>

                    <p>
                        No expenses match your current filters.
                    </p>

                    <a
                        href="expense.php"
                        class="button"
                    >
                        Clear Filters
                    </a>

                </div>

            <?php else: ?>

                <div class="expense-table-wrapper">

                    <table class="expense-table">

                        <thead>

                            <tr>

                                <th>Date</th>

                                <th>Category</th>

                                <th>Description</th>

                                <th>Amount</th>

                                <th>Actions</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($expenses as $expense): ?>

                                <tr>

                                    <td>
                                        <span class="expense-date">
                                            <?= e($expense['expense_date']) ?>
                                        </span>
                                    </td>

                                    <td>

                                        <span class="category-badge">
                                            <?= e(
                                                $expense['category_name']
                                                ?? 'Uncategorized'
                                            ) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <span class="expense-description">
                                            <?= e(
                                                $expense['description']
                                                ?? 'No description'
                                            ) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <strong class="expense-amount">
                                            ₹<?= number_format(
                                                (float) $expense['amount'],
                                                2
                                            ) ?>
                                        </strong>

                                    </td>

                                    <td>

                                        <div class="expense-actions">

                                            <a
                                                href="edit_expense.php?id=<?= (int) $expense['id'] ?>"
                                                class="action-edit"
                                            >
                                                Edit
                                            </a>

                                            <a
                                                href="delete_expense.php?id=<?= (int) $expense['id'] ?>"
                                                class="action-delete"
                                                onclick="return confirm('Are you sure you want to delete this expense?');"
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

    </div>

</main>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>