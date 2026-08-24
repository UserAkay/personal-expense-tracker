<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Budget.php';
require_once __DIR__ . '/../includes/functions.php';


$userId = (int) $_SESSION['user_id'];

$budgetModel = new Budget($pdo);


/*
|--------------------------------------------------------------------------
| Selected Month
|--------------------------------------------------------------------------
*/

$selectedMonth =
    $_GET['month'] ?? date('Y-m');


/*
|--------------------------------------------------------------------------
| Validate Month
|--------------------------------------------------------------------------
*/

if (
    !preg_match(
        '/^\d{4}-\d{2}$/',
        $selectedMonth
    )
) {

    $selectedMonth = date('Y-m');
}


/*
|--------------------------------------------------------------------------
| Get Budgets
|--------------------------------------------------------------------------
|
| IMPORTANT:
| The database stores month_year as:
|
|     YYYY-MM
|
| Example:
|
|     2026-08
|
*/

$budgets =
    $budgetModel->getAllByUser(
        $userId,
        $selectedMonth
    );


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


        <!-- Header -->

        <div class="dashboard-header">

            <div>

                <h1>
                    Budgets
                </h1>

                <p>
                    Set monthly spending limits for your categories.
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

        <?php if (
            isset($_GET['success']) &&
            $_GET['success'] === '1'
        ): ?>

            <div
                style="
                    background:#dcfce7;
                    border:1px solid #22c55e;
                    color:#166534;
                    padding:15px;
                    margin-bottom:25px;
                    border-radius:8px;
                "
            >

                Budget created successfully.

            </div>

        <?php endif; ?>


        <!-- Month Selector -->

        <form
            method="GET"
            style="
                margin-bottom:25px;
                display:flex;
                align-items:end;
                gap:10px;
                flex-wrap:wrap;
            "
        >

            <div>

                <label
                    for="month"
                >
                    Select Month
                </label>

                <br>

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


                <?php foreach (
                    $budgets as $budget
                ): ?>


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
                    | Percentage
                    |--------------------------------------------------------------------------
                    */

                    $percentage =
                        $amount > 0
                        ? ($spent / $amount) * 100
                        : 0;


                    $displayPercentage =
                        min(
                            max($percentage, 0),
                            100
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Status
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


                    <!-- Budget Card -->

                    <div class="budget-card">


                        <!-- Card Header -->

                        <div
                            class="budget-card-header"
                        >

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


                        <!-- Values -->

                        <div
                            class="budget-values"
                        >


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

                        <div
                            class="budget-progress"
                        >

                            <div
                                class="budget-progress-bar"
                                style="
                                    width:
                                    <?= $displayPercentage ?>%;
                                "
                            ></div>

                        </div>


                        <p>

                            <?= number_format(
                                $percentage,
                                1
                            ) ?>% used

                        </p>


                        <!-- Actions -->

                        <div
                            class="budget-actions"
                        >

                            <a
                                href="edit_budget.php?id=<?= (int) $budget['id'] ?>"
                            >
                                Edit
                            </a>


                            |


                            <form
    method="POST"
    action="delete_budget.php"
    style="display:inline;"
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

    <button type="submit">
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