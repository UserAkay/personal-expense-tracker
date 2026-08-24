<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/includes/functions.php';


/*
|--------------------------------------------------------------------------
| Redirect Already Logged-In Users
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user_id'])) {

    header('Location: pages/dashboard.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Form Variables
|--------------------------------------------------------------------------
*/

$errors = [];

$name = '';
$email = '';
$password = '';
$confirmPassword = '';


/*
|--------------------------------------------------------------------------
| Handle Registration
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF Validation
    |--------------------------------------------------------------------------
    */

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken) ||
        !verify_csrf_token($csrfToken)
    ) {

        $errors[] =
            'Invalid security token. Please refresh the page and try again.';
    }


    /*
    |--------------------------------------------------------------------------
    | Get Form Data
    |--------------------------------------------------------------------------
    */

    $name = trim(
        $_POST['name'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );

    $password =
        $_POST['password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Validate Name
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $errors[] =
            'Name is required.';

    } elseif (strlen($name) < 2) {

        $errors[] =
            'Name must contain at least 2 characters.';

    } elseif (strlen($name) > 100) {

        $errors[] =
            'Name cannot exceed 100 characters.';
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Email
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

    } elseif (strlen($email) > 150) {

        $errors[] =
            'Email cannot exceed 150 characters.';
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Password
    |--------------------------------------------------------------------------
    */

    if ($password === '') {

        $errors[] =
            'Password is required.';

    } elseif (strlen($password) < 8) {

        $errors[] =
            'Password must contain at least 8 characters.';
    }


    /*
    |--------------------------------------------------------------------------
    | Confirm Password
    |--------------------------------------------------------------------------
    */

    if ($confirmPassword === '') {

        $errors[] =
            'Please confirm your password.';

    } elseif ($password !== $confirmPassword) {

        $errors[] =
            'Passwords do not match.';
    }


    /*
    |--------------------------------------------------------------------------
    | Create User
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $user = new User($pdo);


            /*
            |--------------------------------------------------------------------------
            | Check Duplicate Email
            |--------------------------------------------------------------------------
            */

            $existingUser =
                $user->findByEmail($email);


            if ($existingUser !== null) {

                $errors[] =
                    'An account with this email already exists.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | Create Account
                |--------------------------------------------------------------------------
                */

                $userId =
                    $user->create(
                        $name,
                        $email,
                        $password
                    );


                /*
                |--------------------------------------------------------------------------
                | Secure Session
                |--------------------------------------------------------------------------
                */

                session_regenerate_id(true);


                $_SESSION['user_id'] =
                    $userId;

                $_SESSION['user_name'] =
                    $name;

                $_SESSION['user_email'] =
                    $email;


                /*
                |--------------------------------------------------------------------------
                | Generate New CSRF Token
                |--------------------------------------------------------------------------
                */

                $_SESSION['csrf_token'] =
                    bin2hex(
                        random_bytes(32)
                    );


                /*
                |--------------------------------------------------------------------------
                | Redirect To Dashboard
                |--------------------------------------------------------------------------
                */

                header(
                    'Location: pages/dashboard.php'
                );

                exit;
            }

        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | Do Not Expose Database Errors
            |--------------------------------------------------------------------------
            */

            $errors[] =
                'Registration failed. Please try again.';
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

    <title>
        Register |
        <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


<div class="auth-container">

    <div class="auth-card">


        <!-- Page Heading -->

        <h1>
            Create Account
        </h1>


        <p>
            Create your account to start tracking your expenses.
        </p>


        <!-- Error Messages -->

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


        <!-- Registration Form -->

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


            <!-- Name -->

            <div class="form-group">

                <label for="name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= e($name) ?>"
                    maxlength="100"
                    autocomplete="name"
                    required
                >

            </div>


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
                    maxlength="150"
                    autocomplete="email"
                    required
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
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

                <small>
                    Password must contain at least 8 characters.
                </small>

            </div>


            <!-- Confirm Password -->

            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

            </div>


            <!-- Submit -->

            <button
                type="submit"
                class="button"
            >
                Create Account
            </button>


        </form>


        <!-- Login Link -->

        <p class="auth-link">

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </p>


    </div>

</div>


</body>

</html>