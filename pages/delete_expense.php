<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Expense.php';
require_once __DIR__ . '/../includes/functions.php';


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Model
|--------------------------------------------------------------------------
*/

$expenseModel = new Expense($pdo);


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

    $_SESSION['error'] =
        'Invalid expense.';

    redirect('expense.php');
}


/*
|--------------------------------------------------------------------------
| Find Expense
|--------------------------------------------------------------------------
|
| The user ID is included so a user cannot delete
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

    $_SESSION['error'] =
        'Expense not found.';

    redirect('expense.php');
}


/*
|--------------------------------------------------------------------------
| Handle Delete Confirmation
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

        $_SESSION['error'] =
            'Invalid form submission. Please try again.';

        redirect(
            'delete_expense.php?id=' .
            (int) $expenseId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Expense
    |--------------------------------------------------------------------------
    */

    try {

        $deleted =
            $expenseModel->delete(
                (int) $expenseId,
                $userId
            );

        if (!$deleted) {

            $_SESSION['error'] =
                'Expense could not be deleted. Please try again.';

            redirect('expense.php');
        }


        $_SESSION['success'] =
            'Expense deleted successfully.';

        redirect('expense.php');

    } catch (PDOException $e) {

        error_log(
            'Expense deletion error: ' .
            $e->getMessage()
        );

        $_SESSION['error'] =
            'Unable to delete the expense. Please try again.';

        redirect('expense.php');
    }
}


/*
|--------------------------------------------------------------------------
| Page Layout
|--------------------------------------------------------------------------
*/

$pageTitle = 'Delete Expense';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="dashboard-main">

    <div class="content-card">


        <h1>
            Delete Expense
        </h1>


        <p>
            Are you sure you want to delete this expense?
        </p>


        <!-- Expense Details -->

        <div>

            <p>

                <strong>
                    Category:
                </strong>

                <?= e(
                    $expense['category_name']
                    ?? 'Uncategorized'
                ) ?>

            </p>


            <p>

                <strong>
                    Amount:
                </strong>

                ₹<?= number_format(
                    (float) $expense['amount'],
                    2
                ) ?>

            </p>


            <p>

                <strong>
                    Description:
                </strong>

                <?= e(
                    $expense['description']
                    ?? ''
                ) ?>

            </p>


            <p>

                <strong>
                    Date:
                </strong>

                <?= e(
                    $expense['expense_date']
                ) ?>

            </p>

        </div>


        <!-- Confirmation Form -->

        <form method="POST">


            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >


            <button
                type="submit"
                class="delete-button"
            >
                Yes, Delete Expense
            </button>


            <a
                href="expense.php"
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