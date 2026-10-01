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
| Only POST Requests
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    redirect('budgets.php');
}


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

    $_SESSION['error'] =
        'Invalid form submission. Please try again.';

    redirect('budgets.php');
}


/*
|--------------------------------------------------------------------------
| Validate Budget ID
|--------------------------------------------------------------------------
*/

$budgetId = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $budgetId === false ||
    $budgetId === null ||
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
*/

try {

    $budget = $budgetModel->findById(
        (int) $budgetId,
        $userId
    );


    if ($budget === null) {

        $_SESSION['error'] =
            'Budget not found.';

        redirect('budgets.php');
    }


    $budgetMonth =
        (string) $budget['month_year'];


    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    $deleted = $budgetModel->delete(
        (int) $budgetId,
        $userId
    );


    if ($deleted) {

        $_SESSION['success'] =
            'Budget deleted successfully.';

    } else {

        $_SESSION['error'] =
            'Budget could not be deleted.';
    }


    redirect(
        'budgets.php?month=' .
        urlencode($budgetMonth)
    );


} catch (PDOException $e) {

    error_log(
        'Budget deletion error: ' .
        $e->getMessage()
    );

    $_SESSION['error'] =
        'Unable to delete the budget. Please try again.';

    redirect('budgets.php');
}