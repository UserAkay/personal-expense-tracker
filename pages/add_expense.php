<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/expense.php';
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
| Form Variables
|--------------------------------------------------------------------------
*/

$errors = [];

$categoryId = '';
$amount = '';
$description = '';
$expenseDate = date('Y-m-d');


/*
|--------------------------------------------------------------------------
| Load Categories
|--------------------------------------------------------------------------
*/

$categoryModel = new Category($pdo);

$categories = $categoryModel->getAllByUser($userId);


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $categoryId = trim(
        $_POST['category_id'] ?? ''
    );

    $amount = trim(
        $_POST['amount'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );

    $expenseDate = trim(
        $_POST['expense_date'] ?? ''
    );


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
    | Category Validation
    |--------------------------------------------------------------------------
    */

    if ($categoryId === '') {

        $errors[] =
            'Please select a category.';

    } elseif (
        filter_var(
            $categoryId,
            FILTER_VALIDATE_INT
        ) === false
    ) {

        $errors[] =
            'Invalid category selected.';
    }


    /*
    |--------------------------------------------------------------------------
    | Amount Validation
    |--------------------------------------------------------------------------
    */

    if ($amount === '') {

        $errors[] =
            'Amount is required.';

    } elseif (
        !is_numeric($amount)
        || (float) $amount <= 0
    ) {

        $errors[] =
            'Amount must be greater than zero.';

    } elseif (
        (float) $amount > 999999999.99
    ) {

        $errors[] =
            'Amount is too large.';
    }


    /*
    |--------------------------------------------------------------------------
    | Description Validation
    |--------------------------------------------------------------------------
    */

    if (strlen($description) > 500) {

        $errors[] =
            'Description cannot exceed 500 characters.';
    }


    /*
    |--------------------------------------------------------------------------
    | Date Validation
    |--------------------------------------------------------------------------
    */

    $dateObject = DateTime::createFromFormat(
        'Y-m-d',
        $expenseDate
    );

    if (
        !$dateObject
        || $dateObject->format('Y-m-d') !== $expenseDate
    ) {

        $errors[] =
            'Please enter a valid expense date.';
    }


    /*
    |--------------------------------------------------------------------------
    | Verify Category Belongs To User
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $selectedCategory =
            $categoryModel->findById(
                (int) $categoryId,
                $userId
            );

        if ($selectedCategory === null) {

            $errors[] =
                'Invalid category selected.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Save expense
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $expenseModel =
                new expense($pdo);

            $expenseModel->create(
                $userId,
                (int) $categoryId,
                (float) $amount,
                $description,
                $expenseDate
            );

            redirect('expense.php');

        } catch (PDOException $e) {

            $errors[] =
                'Unable to save the expense. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Page Layout
|--------------------------------------------------------------------------
*/

$pageTitle = 'Add expense';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="dashboard-main">

    <div class="content-card">

        <h1>
            Add expense
        </h1>

        <p>
            Record a new personal expense.
        </p>


        <?php if (!empty($errors)): ?>

            <div class="error">

                <h3>
                    Please fix the following:
                </h3>

                <ul>

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= e($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <form method="POST" action="">


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
                        Select Category
                    </option>

                    <?php foreach ($categories as $category): ?>

                        <option
                            value="<?= (int) $category['id'] ?>"
                            <?= (string) $categoryId ===
                                (string) $category['id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($category['name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <?php if (empty($categories)): ?>

                    <p>

                        You don't have any categories yet.

                        <a href="categories.php">
                            Create a category first.
                        </a>

                    </p>

                <?php endif; ?>

            </div>


            <!-- Amount -->

            <div class="form-group">

                <label for="amount">
                    Amount
                </label>

                <input
                    type="number"
                    id="amount"
                    name="amount"
                    value="<?= e($amount) ?>"
                    min="0.01"
                    max="999999999.99"
                    step="0.01"
                    placeholder="Enter amount"
                    required
                >

            </div>


            <!-- Description -->

            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    maxlength="500"
                    rows="4"
                    placeholder="Example: Lunch at restaurant"
                ><?= e($description) ?></textarea>

            </div>


            <!-- Date -->

            <div class="form-group">

                <label for="expense_date">
                    expense Date
                </label>

                <input
                    type="date"
                    id="expense_date"
                    name="expense_date"
                    value="<?= e($expenseDate) ?>"
                    required
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
                    Save expense
                </button>

                <a
                    href="expense.php"
                    class="button"
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