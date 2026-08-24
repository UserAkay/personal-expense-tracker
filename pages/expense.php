<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Expense.php';
require_once __DIR__ . '/../classes/Category.php';
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

$categoryModel =
    new Category($pdo);


/*
|--------------------------------------------------------------------------
| Load Categories
|--------------------------------------------------------------------------
*/

$categories =
    $categoryModel->getAllByUser(
        $userId
    );


/*
|--------------------------------------------------------------------------
| Category Filter
|--------------------------------------------------------------------------
*/

$selectedCategory =
    filter_input(
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


/*
|--------------------------------------------------------------------------
| Month Filter
|--------------------------------------------------------------------------
*/

$selectedMonth =
    trim($_GET['month'] ?? '');


if (
    $selectedMonth !== '' &&
    !preg_match(
        '/^\d{4}-\d{2}$/',
        $selectedMonth
    )
) {
    $selectedMonth = '';
}


/*
|--------------------------------------------------------------------------
| Load Expenses
|--------------------------------------------------------------------------
*/

$expenses =
    $expenseModel->getFilteredByUser(
        $userId,
        $selectedCategory,
        $selectedMonth !== ''
            ? $selectedMonth
            : null
    );


/*
|--------------------------------------------------------------------------
| Calculate Filtered Total
|--------------------------------------------------------------------------
*/

$filteredTotal = 0.0;

foreach ($expenses as $expense) {

    $filteredTotal +=
        (float) $expense['amount'];
}


/*
|--------------------------------------------------------------------------
| Page Layout
|--------------------------------------------------------------------------
*/

$pageTitle = 'Expenses';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>


<main class="dashboard-main">

    <div class="content-card">


        <!-- Success Message -->

        <?php if (!empty($_SESSION['success'])): ?>

            <div class="success">

                <?= e($_SESSION['success']) ?>

            </div>

            <?php unset($_SESSION['success']); ?>

        <?php endif; ?>


        <!-- Error Message -->

        <?php if (!empty($_SESSION['error'])): ?>

            <div class="error">

                <?= e($_SESSION['error']) ?>

            </div>

            <?php unset($_SESSION['error']); ?>

        <?php endif; ?>


        <!-- Header -->

        <div
            style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:20px;
                flex-wrap:wrap;
                margin-bottom:25px;
            "
        >

            <div>

                <h1>
                    Expenses
                </h1>

                <p>
                    Manage and filter your personal expenses.
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

        <div class="dashboard-section">

            <h2>
                Filter Expenses
            </h2>


            <form
                method="GET"
                style="
                    display:flex;
                    gap:15px;
                    align-items:end;
                    flex-wrap:wrap;
                "
            >


                <!-- Category -->

                <div>

                    <label for="category_id">
                        Category
                    </label>

                    <br>

                    <select
                        id="category_id"
                        name="category_id"
                    >

                        <option value="">
                            All Categories
                        </option>


                        <?php foreach (
                            $categories
                            as $category
                        ): ?>

                            <option
                                value="<?= (int) $category['id'] ?>"
                                <?= $selectedCategory ===
                                    (int) $category['id']
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= e(
                                    $category['name']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Month -->

                <div>

                    <label for="month">
                        Month
                    </label>

                    <br>

                    <input
                        type="month"
                        id="month"
                        name="month"
                        value="<?= e(
                            $selectedMonth
                        ) ?>"
                    >

                </div>


                <!-- Apply -->

                <button
                    type="submit"
                    class="button"
                >
                    Apply Filters
                </button>


                <!-- Clear -->

                <a
                    href="expense.php"
                    class="button"
                >
                    Clear Filters
                </a>

            </form>

        </div>


        <!-- Summary -->

        <div class="analytics-summary">

            <div class="stat-card">

                <h3>
                    Filtered Spending
                </h3>

                <p>
                    ₹<?= number_format(
                        $filteredTotal,
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
                        count($expenses)
                    ) ?>
                </p>

            </div>

        </div>


        <!-- Expense List -->

        <div class="dashboard-section">

            <?php if (empty($expenses)): ?>

                <div class="empty-state">

                    <h3>
                        No Expenses Found
                    </h3>

                    <p>
                        No expenses match the selected filters.
                    </p>

                    <a
                        href="expense.php"
                        class="button"
                    >
                        Clear Filters
                    </a>

                </div>

            <?php else: ?>


                <div
                    class="table-container"
                    style="overflow-x:auto;"
                >

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
                                    Actions
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
                                            $expense[
                                                'expense_date'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $expense[
                                                'category_name'
                                            ] ??
                                            'Uncategorized'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $expense[
                                                'description'
                                            ] ??
                                            ''
                                        ) ?>

                                    </td>


                                    <td>

                                        ₹<?= number_format(
                                            (float)
                                            $expense['amount'],
                                            2
                                        ) ?>

                                    </td>


                                    <td>

                                        <a
                                            href="edit_expense.php?id=<?= (int) $expense['id'] ?>"
                                        >
                                            Edit
                                        </a>

                                        |

                                        <a
                                            href="delete_expense.php?id=<?= (int) $expense['id'] ?>"
                                        >
                                            Delete
                                        </a>

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