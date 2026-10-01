<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Budget.php';
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

$budgetModel =
    new Budget($pdo);

$categoryModel =
    new Category($pdo);


/*
|--------------------------------------------------------------------------
| Get Budget ID
|--------------------------------------------------------------------------
*/

$budgetId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (
    $budgetId === false ||
    $budgetId === null ||
    $budgetId <= 0
) {

    redirect('budgets.php');
}


/*
|--------------------------------------------------------------------------
| Find Budget
|--------------------------------------------------------------------------
*/

$budget =
    $budgetModel->findById(
        (int) $budgetId,
        $userId
    );


/*
|--------------------------------------------------------------------------
| Ownership / Existence Check
|--------------------------------------------------------------------------
*/

if ($budget === null) {

    $_SESSION['error'] =
        'Budget not found.';

    redirect('budgets.php');
}


/*
|--------------------------------------------------------------------------
| Get User Categories
|--------------------------------------------------------------------------
*/

$categories =
    $categoryModel->getAllByUser(
        $userId
    );


/*
|--------------------------------------------------------------------------
| Form Variables
|--------------------------------------------------------------------------
*/

$errors = [];

$categoryId =
    (int) $budget['category_id'];

$amount =
    (string) $budget['amount'];

$budgetMonth =
    (string) $budget['month_year'];


/*
|--------------------------------------------------------------------------
| Handle POST Request
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | CSRF Validation
    |--------------------------------------------------------------------------
    */

    if (
        !verify_csrf_token(
            $_POST['csrf_token'] ?? ''
        )
    ) {

        $errors[] =
            'Invalid form submission. Please try again.';
    }


    /*
    |--------------------------------------------------------------------------
    | Get Submitted Values
    |--------------------------------------------------------------------------
    */

    $categoryId = filter_var(
        $_POST['category_id'] ?? 0,
        FILTER_VALIDATE_INT
    );

    if ($categoryId === false) {
        $categoryId = 0;
    }


    $amount =
        trim(
            $_POST['amount'] ?? ''
        );


    $budgetMonth =
        trim(
            $_POST['month_year'] ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | Category Validation
    |--------------------------------------------------------------------------
    */

    if ($categoryId <= 0) {

        $errors[] =
            'Please select a category.';
    }


    /*
    |--------------------------------------------------------------------------
    | Amount Validation
    |--------------------------------------------------------------------------
    */

    if ($amount === '') {

        $errors[] =
            'Budget amount is required.';

    } elseif (
        !is_numeric($amount)
    ) {

        $errors[] =
            'Budget amount must be a valid number.';

    } elseif (
        (float) $amount <= 0
    ) {

        $errors[] =
            'Budget amount must be greater than zero.';

    } elseif (
        (float) $amount > 999999999.99
    ) {

        $errors[] =
            'Budget amount is too large.';
    }


    /*
    |--------------------------------------------------------------------------
    | Month Validation
    |--------------------------------------------------------------------------
    */

    if (
        !preg_match(
            '/^\d{4}-\d{2}$/',
            $budgetMonth
        )
    ) {

        $errors[] =
            'Please select a valid month.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Validate Actual Calendar Month
        |--------------------------------------------------------------------------
        */

        $monthObject =
            DateTime::createFromFormat(
                '!Y-m',
                $budgetMonth
            );

        if (
            !$monthObject ||
            $monthObject->format('Y-m') !==
                $budgetMonth
        ) {

            $errors[] =
                'Please select a valid month.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Verify Category Belongs To User
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

    $category =
        $categoryModel->findById(
            (int) $categoryId,
            $userId
        );

    if ($category === null) {

        $errors[] =
            'Invalid category selected.';
    }
}


    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Budget
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        if (
            $budgetModel->exists(
                $userId,
                (int) $categoryId,
                $budgetMonth,
                (int) $budgetId
            )
        ) {

            $errors[] =
                'A budget already exists for this category and month.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Update Budget
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $updated =
                $budgetModel->update(
                    (int) $budgetId,
                    $userId,
                    (int) $categoryId,
                    (float) $amount,
                    $budgetMonth
                );


            if ($updated) {

                $_SESSION['success'] =
                    'Budget updated successfully.';

                redirect(
                    'budgets.php?month=' .
                    urlencode($budgetMonth)
                );
            }


            $errors[] =
                'Budget could not be updated. Please try again.';

        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | Log Database Error
            |--------------------------------------------------------------------------
            |
            | Do NOT expose the exception message to the user.
            |
            */

            error_log(
                'Budget update error: ' .
                $e->getMessage()
            );


            /*
            |--------------------------------------------------------------------------
            | Handle Duplicate Constraint
            |--------------------------------------------------------------------------
            */

            if (
                isset($e->errorInfo[1]) &&
                (int) $e->errorInfo[1] === 1062
            ) {

                $errors[] =
                    'A budget already exists for this category and month.';

            } else {

                $errors[] =
                    'Unable to update the budget. Please try again.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Page Layout
|--------------------------------------------------------------------------
*/

$pageTitle = 'Edit Budget';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>


<main class="dashboard-main">

    <div class="content-card">


        <!-- Header -->

        <div
            style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                margin-bottom:25px;
                gap:20px;
                flex-wrap:wrap;
            "
        >

            <div>

                <h1>
                    Edit Budget
                </h1>

                <p>
                    Update your monthly spending limit.
                </p>

            </div>


            <a
                href="budgets.php?month=<?= e($budgetMonth) ?>"
                class="button"
            >
                ← Back to Budgets
            </a>

        </div>


        <!-- Error Messages -->

        <?php if (!empty($errors)): ?>

            <div 
                class="error-message"
                role="alert"
            >

                <h3>
                    Please fix the following:
                </h3>

                <ul>

                    <?php foreach (
                        $errors as $error
                    ): ?>

                        <li>
                            <?= e($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <!-- Edit Form -->

        <form
            method="POST"
            action=""
        >


            <!-- CSRF -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >


            <!-- Category -->

            <div class="form-group">

                <label for="category_id">
                    Category
                </label>


                <select
                    id="category_id"
                    name="category_id"
                    required
                >

                    <option value="">
                        -- Select Category --
                    </option>


                    <?php foreach (
                        $categories as $category
                    ): ?>

                        <option
                            value="<?= (int) $category['id'] ?>"
                            <?= (
                                (int) $categoryId ===
                                (int) $category['id']
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= e(
                                $category['name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Amount -->

            <div class="form-group">

                <label for="amount">
                    Monthly Budget Amount
                </label>


                <input
                    type="number"
                    id="amount"
                    name="amount"
                    value="<?= e($amount) ?>"
                    min="0.01"
                    max="999999999.99"
                    step="0.01"
                    placeholder="Example: 5000"
                    required
                >

            </div>


            <!-- Month -->

            <div class="form-group">

                <label for="month_year">
                    Budget Month
                </label>


                <input
                    type="month"
                    id="month_year"
                    name="month_year"
                    value="<?= e($budgetMonth) ?>"
                    required
                >

            </div>


            <!-- Buttons -->

            <button
                type="submit"
                class="button"
            >
                Update Budget
            </button>


            <a
                href="budgets.php?month=<?= e($budgetMonth) ?>"
                class="button"
            >
                Cancel
            </a>


        </form>

    </div>

</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>