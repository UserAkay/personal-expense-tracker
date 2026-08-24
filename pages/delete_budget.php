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

$userId =
    (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Model
|--------------------------------------------------------------------------
*/

$budgetModel =
    new Budget($pdo);


/*
|--------------------------------------------------------------------------
| Only Allow POST Requests
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {

    redirect('budgets.php');
}


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

    redirect('budgets.php');
}


/*
|--------------------------------------------------------------------------
| Get Budget ID
|--------------------------------------------------------------------------
*/

$budgetId = filter_var(
    $_POST['id'] ?? 0,
    FILTER_VALIDATE_INT
);


if (
    $budgetId === false ||
    $budgetId <= 0
) {

    $_SESSION['error'] =
        'Invalid budget.';

    redirect('budgets.php');
}


/*
|--------------------------------------------------------------------------
| Find Budget
|--------------------------------------------------------------------------
|
| The user ID is included so a user cannot delete
| another user's budget by changing the ID.
|
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
| Remember Budget Month
|--------------------------------------------------------------------------
*/

$budgetMonth =
    (string) $budget['month_year'];


/*
|--------------------------------------------------------------------------
| Delete Budget
|--------------------------------------------------------------------------
*/

try {

    $deleted =
        $budgetModel->delete(
            (int) $budgetId,
            $userId
        );


    if ($deleted) {

        $_SESSION['success'] =
            'Budget deleted successfully.';

        redirect(
            'budgets.php?month=' .
            urlencode($budgetMonth)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Failed
    |--------------------------------------------------------------------------
    */

    $_SESSION['error'] =
        'Budget could not be deleted. Please try again.';

    redirect(
        'budgets.php?month=' .
        urlencode($budgetMonth)
    );


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | Log Database Error
    |--------------------------------------------------------------------------
    |
    | Never display the raw database exception to the user.
    |
    */

    error_log(
        'Budget deletion error: ' .
        $e->getMessage()
    );


    $_SESSION['error'] =
        'Unable to delete the budget. Please try again.';

    redirect(
        'budgets.php?month=' .
        urlencode($budgetMonth)
    );
}