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

$expenseModel = new Expense($pdo);
$categoryModel = new Category($pdo);


/*
|--------------------------------------------------------------------------
| Get Expense ID
|--------------------------------------------------------------------------
*/

$expenseId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (
    $expenseId === false ||
    $expenseId === null ||
    $expenseId <= 0
) {
    $_SESSION['error'] = 'Invalid expense.';
    redirect('expense.php');
}


/*
|--------------------------------------------------------------------------
| Find Expense
|--------------------------------------------------------------------------
|
| The user ID is included so a user cannot edit
| another user's expense by changing the ID.
|
*/

$expense = $expenseModel->findById(
    (int) $expenseId,
    $userId
);


/*
|--------------------------------------------------------------------------
| Ownership / Existence Check
|--------------------------------------------------------------------------
*/

if ($expense === null) {
    $_SESSION['error'] = 'Expense not found.';
    redirect('expense.php');
}


/*
|--------------------------------------------------------------------------
| Load Categories
|--------------------------------------------------------------------------
*/

$categories = $categoryModel->getAllByUser($userId);


/*
|--------------------------------------------------------------------------
| Form Variables
|--------------------------------------------------------------------------
*/

$errors = [];

$categoryId = (int) $expense['category_id'];
$amount = (string) $expense['amount'];
$description = (string) (
    $expense['description'] ?? ''
);
$expenseDate = (string) $expense['expense_date'];


/*
|--------------------------------------------------------------------------
| Handle Form Submission
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
            'Amount is required.';

    } elseif (
        !is_numeric($amount) ||
        (float) $amount <= 0
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
        '!Y-m-d',
        $expenseDate
    );

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $expenseDate
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
    | Update Expense
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $updated =
                $expenseModel->update(
                    (int) $expenseId,
                    $userId,
                    (int) $categoryId,
                    (float) $amount,
                    $description,
                    $expenseDate
                );

            if ($updated) {

                $_SESSION['success'] =
                    'Expense updated successfully.';

                redirect('expense.php');
            }

            $errors[] =
                'Expense could not be updated. Please try again.';

        } catch (PDOException $e) {

            error_log(
                'Expense update error: ' .
                $e->getMessage()
            );

            $errors[] =
                'Unable to update the expense. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Page Layout
|--------------------------------------------------------------------------
*/

$pageTitle = 'Edit Expense';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="dashboard-main">

    <div class="content-card">

        <h1>
            Edit Expense
        </h1>

        <p>
            Update the details of your expense.
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
                    Expense Date
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
                    Update Expense
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