<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
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
| Budget Model
|--------------------------------------------------------------------------
*/

$budgetModel = new Budget($pdo);


/*
|--------------------------------------------------------------------------
| Selected Month
|--------------------------------------------------------------------------
*/

$selectedMonth =
    is_string($_GET['month'] ?? null)
        ? $_GET['month']
        : date('Y-m');


/*
|--------------------------------------------------------------------------
| Validate Month Format
|--------------------------------------------------------------------------
*/

if (
    !preg_match(
        '/^\d{4}-\d{2}$/',
        $selectedMonth
    )
) {

    $selectedMonth = date('Y-m');

} else {

    $monthObject =
        DateTime::createFromFormat(
            '!Y-m',
            $selectedMonth
        );

    if (
        !$monthObject ||
        $monthObject->format('Y-m') !== $selectedMonth
    ) {

        $selectedMonth = date('Y-m');
    }
}


/*
|--------------------------------------------------------------------------
| Session Messages
|--------------------------------------------------------------------------
*/

$success =
    isset($_SESSION['success'])
        ? (string) $_SESSION['success']
        : '';

$error =
    isset($_SESSION['error'])
        ? (string) $_SESSION['error']
        : '';


unset(
    $_SESSION['success'],
    $_SESSION['error']
);


/*
|--------------------------------------------------------------------------
| Get Budgets
|--------------------------------------------------------------------------
*/

try {

    $budgets =
        $budgetModel->getAllByUser(
            $userId,
            $selectedMonth
        );

} catch (PDOException $e) {

    error_log(
        'Budget loading error: ' .
        $e->getMessage()
    );

    $budgets = [];

    $error =
        'Unable to load budgets. Please try again.';
}


/*
|--------------------------------------------------------------------------
| Page Title
|--------------------------------------------------------------------------
*/

$pageTitle = 'Budgets';


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
                    Budgets
                </h1>

                <p>
                    Set monthly spending limits and track your progress.
                </p>

            </div>


            <a
                href="add_budget.php"
                class="button"
            >
                + Add Budget
            </a>

        </div>


        <!-- Success Message -->

        <?php if ($success !== ''): ?>

            <div
                class="success-message"
                role="status"
            >

                <?= e($success) ?>

            </div>

        <?php endif; ?>


        <!-- Error Message -->

        <?php if ($error !== ''): ?>

            <div
                class="error-message"
                role="alert"
            >

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <!-- Month Selector -->

        <form
            method="GET"
            class="month-selector"
        >

            <div>

                <label for="month">
                    Select Month
                </label>


                <input
                    type="month"
                    id="month"
                    name="month"
                    value="<?= e($selectedMonth) ?>"
                    required
                >

            </div>


            <button
                type="submit"
                class="button"
            >
                View
            </button>

        </form>


        <!-- Budget List -->

        <?php if (empty($budgets)): ?>

            <div class="empty-state">

                <h3>
                    No budgets for this month
                </h3>

                <p>
                    Create a budget to start tracking your spending.
                </p>


                <a
                    href="add_budget.php"
                    class="button"
                >
                    Create Budget
                </a>

            </div>


        <?php else: ?>


            <div class="budget-grid">


                <?php foreach ($budgets as $budget): ?>


                    <?php

                    /*
                    |--------------------------------------------------------------------------
                    | Budget Values
                    |--------------------------------------------------------------------------
                    */

                    $amount =
                        (float) $budget['amount'];

                    $spent =
                        (float) $budget['spent'];

                    $remaining =
                        $amount - $spent;


                    /*
                    |--------------------------------------------------------------------------
                    | Percentage Calculation
                    |--------------------------------------------------------------------------
                    */

                    $percentage =
                        $amount > 0
                            ? ($spent / $amount) * 100
                            : 0;


                    /*
                    |--------------------------------------------------------------------------
                    | Progress Bar Percentage
                    |--------------------------------------------------------------------------
                    |
                    | CSS width should never exceed 100%.
                    |
                    */

                    $displayPercentage =
                        min(
                            max($percentage, 0),
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

                        $statusClass =
                            'budget-status-danger';

                    } elseif ($percentage >= 80) {

                        $status =
                            'Almost Used';

                        $statusClass =
                            'budget-status-warning';

                    } else {

                        $status =
                            'On Track';

                        $statusClass =
                            'budget-status-success';
                    }

                    ?>


                    <!-- Budget Card -->

                    <div class="budget-card">


                        <!-- Card Header -->

                        <div class="budget-card-header">

                            <h2>

                                <?= e(
                                    $budget['category_name']
                                ) ?>

                            </h2>


                            <span
                                class="<?= e($statusClass) ?>"
                            >

                                <?= e($status) ?>

                            </span>

                        </div>


                        <!-- Budget Values -->

                        <div class="budget-values">


                            <div>

                                <small>
                                    Budget
                                </small>

                                <strong>

                                    ₹<?= number_format(
                                        $amount,
                                        2
                                    ) ?>

                                </strong>

                            </div>


                            <div>

                                <small>
                                    Spent
                                </small>

                                <strong>

                                    ₹<?= number_format(
                                        $spent,
                                        2
                                    ) ?>

                                </strong>

                            </div>


                            <div>

                                <small>
                                    Remaining
                                </small>

                                <strong>

                                    ₹<?= number_format(
                                        $remaining,
                                        2
                                    ) ?>

                                </strong>

                            </div>


                        </div>


                        <!-- Progress Bar -->

                        <div class="budget-progress">

                            <div
                                class="budget-progress-bar"
                                style="
                                    width: <?= $displayPercentage ?>%;
                                "
                            ></div>

                        </div>


                        <!-- Percentage -->

                        <p class="budget-percentage">

                            <?= number_format(
                                $percentage,
                                1
                            ) ?>% used

                        </p>


                        <!-- Actions -->

                        <div class="budget-actions">


                            <a
                                href="edit_budget.php?id=<?= (int) $budget['id'] ?>"
                            >
                                Edit
                            </a>


                            <form
                                method="POST"
                                action="delete_budget.php"
                                onsubmit="return confirm('Delete this budget?');"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrf_token()) ?>"
                                >


                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $budget['id'] ?>"
                                >


                                <button
                                    type="submit"
                                    class="delete-button"
                                >
                                    Delete
                                </button>

                            </form>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </div>

</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>