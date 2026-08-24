<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| Start Session
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Required Files
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| Redirect Already Authenticated Users
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['user_id'])
    && is_numeric($_SESSION['user_id'])
    && (int) $_SESSION['user_id'] > 0
) {

    header(
        'Location: pages/dashboard.php',
        true,
        302
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Form Variables
|--------------------------------------------------------------------------
*/

$errors = [];

$email = '';


/*
|--------------------------------------------------------------------------
| Handle Login
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | Get Form Data
    |--------------------------------------------------------------------------
    */

    $email = trim(
        is_string($_POST['email'] ?? null)
            ? $_POST['email']
            : ''
    );

    $password =
        is_string($_POST['password'] ?? null)
            ? $_POST['password']
            : '';

    $csrfToken =
        is_string($_POST['csrf_token'] ?? null)
            ? $_POST['csrf_token']
            : '';


    /*
    |--------------------------------------------------------------------------
    | CSRF Validation
    |--------------------------------------------------------------------------
    */

    if (
        !verify_csrf_token($csrfToken)
    ) {

        $errors[] =
            'Invalid security token. Please refresh the page and try again.';
    }


    /*
    |--------------------------------------------------------------------------
    | Email Validation
    |--------------------------------------------------------------------------
    */

    if ($email === '') {

        $errors[] =
            'Email is required.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors[] =
            'Please enter a valid email address.';
    }


    /*
    |--------------------------------------------------------------------------
    | Password Validation
    |--------------------------------------------------------------------------
    */

    if ($password === '') {

        $errors[] =
            'Password is required.';
    }


    /*
    |--------------------------------------------------------------------------
    | Authenticate User
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $userModel =
                new User($pdo);

            $authenticatedUser =
                $userModel->authenticate(
                    $email,
                    $password
                );


            /*
            |--------------------------------------------------------------------------
            | Authentication Failed
            |--------------------------------------------------------------------------
            */

            if ($authenticatedUser === null) {

                $errors[] =
                    'Invalid email or password.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | Regenerate Session ID
                |--------------------------------------------------------------------------
                |
                | Prevents session fixation after successful authentication.
                |
                */

                session_regenerate_id(true);


                /*
                |--------------------------------------------------------------------------
                | Store User Session
                |--------------------------------------------------------------------------
                */

                $_SESSION['user_id'] =
                    (int) $authenticatedUser['id'];

                $_SESSION['user_name'] =
                    (string) $authenticatedUser['name'];

                $_SESSION['user_email'] =
                    (string) $authenticatedUser['email'];


                /*
                |--------------------------------------------------------------------------
                | Regenerate CSRF Token
                |--------------------------------------------------------------------------
                */

                regenerate_csrf_token();


                /*
                |--------------------------------------------------------------------------
                | Redirect
                |--------------------------------------------------------------------------
                */

                header(
                    'Location: pages/dashboard.php',
                    true,
                    302
                );

                exit;
            }

        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | Log Internal Error
            |--------------------------------------------------------------------------
            */

            error_log(
                'Login database error: '
                . $e->getMessage()
            );


            /*
            |--------------------------------------------------------------------------
            | User-Friendly Error
            |--------------------------------------------------------------------------
            */

            $errors[] =
                'Login failed. Please try again.';
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Login to your Personal Expense Tracker account."
    >

    <title>
        Login - Personal Expense Tracker
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>

<div class="auth-container">

    <div class="auth-card">


        <!-- Header -->

        <h1>
            Login
        </h1>

        <p>
            Login to your expense tracker.
        </p>


        <!-- Error Messages -->

        <?php if (!empty($errors)): ?>

            <div
                class="error"
                role="alert"
            >

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


        <!-- Login Form -->

        <form
            method="POST"
            action=""
        >

            <!-- CSRF Token -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >


            <!-- Email -->

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($email) ?>"
                    autocomplete="email"
                    maxlength="255"
                    required
                    autofocus
                >

            </div>


            <!-- Password -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >

            </div>


            <!-- Submit -->

            <button
                type="submit"
                class="button"
            >
                Login
            </button>

        </form>


        <!-- Registration Link -->

        <p class="auth-link">

            Don't have an account?

            <a href="register.php">
                Create Account
            </a>

        </p>


    </div>

</div>

</body>

</html>