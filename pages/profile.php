<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../includes/functions.php';


/*
|--------------------------------------------------------------------------
| Current User
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| User Model
|--------------------------------------------------------------------------
*/

$userModel = new User($pdo);


/*
|--------------------------------------------------------------------------
| Load User
|--------------------------------------------------------------------------
*/

$user = $userModel->findById($userId);


if ($user === null) {

    $_SESSION = [];

    session_destroy();

    header(
        'Location: ../login.php',
        true,
        302
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Form State
|--------------------------------------------------------------------------
*/

$errors = [];

$success = '';

$name =
    (string) $user['name'];

$email =
    (string) $user['email'];


/*
|--------------------------------------------------------------------------
| Handle POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    $csrfToken =
        is_string($_POST['csrf_token'] ?? null)
            ? $_POST['csrf_token']
            : '';


    if (!verify_csrf_token($csrfToken)) {

        $errors[] =
            'Invalid security token. Please refresh the page and try again.';
    }


    $action =
        is_string($_POST['action'] ?? null)
            ? $_POST['action']
            : '';


    /*
    |--------------------------------------------------------------------------
    | Update Profile
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors)
        && $action === 'profile'
    ) {

        $name =
            trim(
                is_string($_POST['name'] ?? null)
                    ? $_POST['name']
                    : ''
            );

        $email =
            strtolower(
                trim(
                    is_string($_POST['email'] ?? null)
                        ? $_POST['email']
                        : ''
                )
            );


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

        } elseif (strlen($email) > 255) {

            $errors[] =
                'Email address is too long.';
        }


        /*
        |--------------------------------------------------------------------------
        | Duplicate Email
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            if (
                $userModel->emailExists(
                    $email,
                    $userId
                )
            ) {

                $errors[] =
                    'This email address is already in use.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                $stmt =
                    $pdo->prepare(
                        '
                        UPDATE users
                        SET
                            name = :name,
                            email = :email
                        WHERE
                            id = :id
                        LIMIT 1
                        '
                    );


                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':id' => $userId
                ]);


                $_SESSION['user_name'] =
                    $name;

                $_SESSION['user_email'] =
                    $email;


                $success =
                    'Profile updated successfully.';


                regenerate_csrf_token();


            } catch (PDOException $e) {

                error_log(
                    'Profile update error: '
                    . $e->getMessage()
                );

                $errors[] =
                    'Unable to update your profile.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Change Password
    |--------------------------------------------------------------------------
    */

    elseif (
        empty($errors)
        && $action === 'password'
    ) {

        $currentPassword =
            is_string(
                $_POST['current_password'] ?? null
            )
                ? $_POST['current_password']
                : '';


        $newPassword =
            is_string(
                $_POST['new_password'] ?? null
            )
                ? $_POST['new_password']
                : '';


        $confirmPassword =
            is_string(
                $_POST['confirm_password'] ?? null
            )
                ? $_POST['confirm_password']
                : '';


        /*
        |--------------------------------------------------------------------------
        | Current Password
        |--------------------------------------------------------------------------
        */

        if ($currentPassword === '') {

            $errors[] =
                'Current password is required.';

        } elseif (
            !password_verify(
                $currentPassword,
                (string) $user['password']
            )
        ) {

            $errors[] =
                'Current password is incorrect.';
        }


        /*
        |--------------------------------------------------------------------------
        | New Password
        |--------------------------------------------------------------------------
        */

        if ($newPassword === '') {

            $errors[] =
                'New password is required.';

        } elseif (strlen($newPassword) < 8) {

            $errors[] =
                'New password must contain at least 8 characters.';

        } elseif (strlen($newPassword) > 255) {

            $errors[] =
                'New password is too long.';
        }


        /*
        |--------------------------------------------------------------------------
        | Confirm Password
        |--------------------------------------------------------------------------
        */

        if (
            $newPassword !== ''
            && $newPassword !== $confirmPassword
        ) {

            $errors[] =
                'New passwords do not match.';
        }


        /*
        |--------------------------------------------------------------------------
        | Update Password
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            $hashedPassword =
                password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );


            if ($hashedPassword === false) {

                $errors[] =
                    'Unable to secure the new password.';

            } else {

                try {

                    $stmt =
                        $pdo->prepare(
                            '
                            UPDATE users
                            SET
                                password = :password
                            WHERE
                                id = :id
                            LIMIT 1
                            '
                        );


                    $stmt->execute([
                        ':password' =>
                            $hashedPassword,

                        ':id' =>
                            $userId
                    ]);


                    $success =
                        'Password changed successfully.';


                    regenerate_csrf_token();


                    /*
                    |--------------------------------------------------------------------------
                    | Regenerate Session ID
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(true);


                    $_SESSION['user_id'] =
                        $userId;

                    $_SESSION['user_name'] =
                        $name;

                    $_SESSION['user_email'] =
                        $email;


                } catch (PDOException $e) {

                    error_log(
                        'Password change error: '
                        . $e->getMessage()
                    );

                    $errors[] =
                        'Unable to change your password.';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$pageTitle = 'Profile';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>


<main class="dashboard-main">

    <div class="content-card">


        <div class="dashboard-header">

            <div>

                <h1>
                    Profile
                </h1>

                <p>
                    Manage your account information and password.
                </p>

            </div>

        </div>


        <?php if (!empty($errors)): ?>

            <div
                class="error-message"
                role="alert"
            >

                <ul>

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= e($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <?php if ($success !== ''): ?>

            <div
                class="success-message"
                role="status"
            >
                <?= e($success) ?>
            </div>

        <?php endif; ?>


        <!-- Profile Information -->

        <div class="dashboard-section">

            <h2>
                Profile Information
            </h2>


            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >


                <input
                    type="hidden"
                    name="action"
                    value="profile"
                >


                <div class="form-group">

                    <label for="name">
                        Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e($name) ?>"
                        maxlength="100"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e($email) ?>"
                        maxlength="255"
                        autocomplete="email"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="button"
                >
                    Save Changes
                </button>

            </form>

        </div>


        <!-- Password -->

        <div class="dashboard-section">

            <h2>
                Change Password
            </h2>


            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >


                <input
                    type="hidden"
                    name="action"
                    value="password"
                >


                <div class="form-group">

                    <label for="current_password">
                        Current Password
                    </label>

                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="new_password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        minlength="8"
                        maxlength="255"
                        autocomplete="new-password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="8"
                        maxlength="255"
                        autocomplete="new-password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="button"
                >
                    Change Password
                </button>

            </form>

        </div>


        <!-- Account Information -->

        <div class="dashboard-section">

            <h2>
                Account Information
            </h2>

            <p>
                <strong>
                    Account created:
                </strong>

                <?= e($user['created_at']) ?>
            </p>

        </div>


    </div>

</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>