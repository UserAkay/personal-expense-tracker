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

$budgetModel = new Budget($pdo);
$categoryModel = new Category($pdo);


/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
*/

$errors = [];

$categoryId = 0;
$amount = '';
$budgetMonth = date('Y-m');


/*
|--------------------------------------------------------------------------
| Get User Categories
|--------------------------------------------------------------------------
*/

try {

    $categories = $categoryModel->getAllByUser($userId);

} catch (PDOException $e) {

    error_log(
        'Category loading error in add_budget.php: ' .
        $e->getMessage()
    );

    $categories = [];

    $errors[] =
        'Unable to load categories. Please try again.';
}


/*
|--------------------------------------------------------------------------
| Process Form
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | CSRF Validation
    |--------------------------------------------------------------------------
    */

    $csrfToken =
        is_string($_POST['csrf_token'] ?? null)
            ? $_POST['csrf_token']
            : '';

    if (!verify_csrf_token($csrfToken)) {

        $errors[] =
            'Invalid form submission. Please refresh the page and try again.';
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


    $amount = is_string($_POST['amount'] ?? null)
        ? trim($_POST['amount'])
        : '';


    $budgetMonth = is_string($_POST['month_year'] ?? null)
        ? trim($_POST['month_year'])
        : '';


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

    } elseif (!is_numeric($amount)) {

        $errors[] =
            'Budget amount must be a valid number.';

    } elseif ((float) $amount <= 0) {

        $errors[] =
            'Budget amount must be greater than zero.';

    } elseif ((float) $amount > 999999999.99) {

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

        $monthObject =
            DateTime::createFromFormat(
                '!Y-m',
                $budgetMonth
            );

        if (
            !$monthObject ||
            $monthObject->format('Y-m') !== $budgetMonth
        ) {

            $errors[] =
                'Please select a valid month.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Check Category Belongs To Current User
    |--------------------------------------------------------------------------
    |
    | This is the important part.
    |
    | We directly ask Category::findById() whether the selected
    | category belongs to the logged-in user.
    |
    */

    if (empty($errors)) {

        try {

            $category =
                $categoryModel->findById(
                    (int) $categoryId,
                    $userId
                );

            if ($category === null) {

                $errors[] =
                    'Invalid category selected.';
            }

        } catch (PDOException $e) {

            error_log(
                'Category validation error in add_budget.php: ' .
                $e->getMessage()
            );

            $errors[] =
                'Unable to validate the selected category.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Budget
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $budgetExists =
                $budgetModel->exists(
                    $userId,
                    (int) $categoryId,
                    $budgetMonth
                );

            if ($budgetExists) {

                $errors[] =
                    'A budget already exists for this category and month.';
            }

        } catch (PDOException $e) {

            error_log(
                'Budget duplicate check error: ' .
                $e->getMessage()
            );

            $errors[] =
                'Unable to check existing budgets. Please try again.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create Budget
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $budgetId =
                $budgetModel->create(
                    $userId,
                    (int) $categoryId,
                    (float) $amount,
                    $budgetMonth
                );


            if ($budgetId > 0) {

                $_SESSION['success'] =
                    'Budget created successfully.';

                redirect(
                    'budgets.php?month=' .
                    urlencode($budgetMonth)
                );
            }


            $errors[] =
                'Budget could not be created. Please try again.';

        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | Log Database Error
            |--------------------------------------------------------------------------
            */

            error_log(
                'Budget creation error: ' .
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
                    'Unable to create the budget. Please try again.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Page Layout
|--------------------------------------------------------------------------
*/

$pageTitle = 'Add Budget';

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
                    Add Budget
                </h1>

                <p>
                    Set a monthly spending limit for a category.
                </p>

            </div>


            <a
                href="budgets.php"
                class="button"
            >
                ← Back to Budgets
            </a>

        </div>


        <!-- Errors -->

        <?php if (!empty($errors)): ?>

            <div
                style="
                    background:#fee2e2;
                    border:1px solid #ef4444;
                    color:#991b1b;
                    padding:15px;
                    margin-bottom:25px;
                    border-radius:8px;
                "
                role="alert"
            >

                <strong>
                    Please fix the following:
                </strong>

                <ul
                    style="
                        margin-top:10px;
                        margin-bottom:0;
                    "
                >

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= e($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <!-- Budget Form -->

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

            <div
                style="
                    margin-bottom:20px;
                "
            >

                <label for="category_id">

                    <strong>
                        Category
                    </strong>

                </label>

                <br><br>

                <select
                    id="category_id"
                    name="category_id"
                    required
                    style="
                        width:100%;
                        max-width:500px;
                        padding:10px;
                        border:1px solid #ccc;
                        border-radius:6px;
                    "
                >

                    <option value="">
                        -- Select Category --
                    </option>


                    <?php foreach ($categories as $category): ?>

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

                            <?= e($category['name']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Amount -->

            <div
                style="
                    margin-bottom:20px;
                "
            >

                <label for="amount">

                    <strong>
                        Monthly Budget Amount
                    </strong>

                </label>

                <br><br>

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
                    style="
                        width:100%;
                        max-width:500px;
                        padding:10px;
                        border:1px solid #ccc;
                        border-radius:6px;
                    "
                >

            </div>


            <!-- Month -->

            <div
                style="
                    margin-bottom:25px;
                "
            >

                <label for="month_year">

                    <strong>
                        Budget Month
                    </strong>

                </label>

                <br><br>

                <input
                    type="month"
                    id="month_year"
                    name="month_year"
                    value="<?= e($budgetMonth) ?>"
                    required
                    style="
                        width:100%;
                        max-width:500px;
                        padding:10px;
                        border:1px solid #ccc;
                        border-radius:6px;
                    "
                >

            </div>


            <!-- Buttons -->

            <div>

                <button
                    type="submit"
                    class="button"
                    <?= empty($categories)
                        ? 'disabled'
                        : '' ?>
                >
                    Create Budget
                </button>


                <a
                    href="budgets.php"
                    class="button"
                    style="margin-left:10px;"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>