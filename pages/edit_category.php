<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
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
| Category Model
|--------------------------------------------------------------------------
*/

$categoryModel = new Category($pdo);


/*
|--------------------------------------------------------------------------
| Form State
|--------------------------------------------------------------------------
*/

$errors = [];

$categoryId =
    filter_var(
        $_GET['id'] ?? null,
        FILTER_VALIDATE_INT
    );


/*
|--------------------------------------------------------------------------
| Validate Category ID
|--------------------------------------------------------------------------
*/

if (
    $categoryId === false
    || $categoryId <= 0
) {

    header(
        'Location: categories.php',
        true,
        302
    );

    exit;
}

$categoryId = (int) $categoryId;


/*
|--------------------------------------------------------------------------
| Find Category
|--------------------------------------------------------------------------
|
| The user ID is included so a user cannot edit another
| user's category by changing the URL ID.
|
*/

$category =
    $categoryModel->findById(
        $categoryId,
        $userId
    );


if ($category === null) {

    header(
        'Location: categories.php',
        true,
        302
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Current Category Name
|--------------------------------------------------------------------------
*/

$name =
    (string) $category['name'];


/*
|--------------------------------------------------------------------------
| Update Category
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF Validation
    |--------------------------------------------------------------------------
    */

    $csrfToken =
        is_string(
            $_POST['csrf_token'] ?? null
        )
            ? $_POST['csrf_token']
            : '';


    if (
        !verify_csrf_token(
            $csrfToken
        )
    ) {

        $errors[] =
            'Invalid security token. Please refresh the page and try again.';
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Name
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $name =
            trim(
                is_string(
                    $_POST['name'] ?? null
                )
                    ? $_POST['name']
                    : ''
            );


        if ($name === '') {

            $errors[] =
                'Category name is required.';

        } elseif (
            strlen($name) < 2
        ) {

            $errors[] =
                'Category name must contain at least 2 characters.';

        } elseif (
            strlen($name) > 50
        ) {

            $errors[] =
                'Category name cannot exceed 50 characters.';

        } elseif (
            $categoryModel->existsByName(
                $name,
                $userId,
                $categoryId
            )
        ) {

            $errors[] =
                'Another category with this name already exists.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $updated =
                $categoryModel->update(
                    $categoryId,
                    $userId,
                    $name
                );


            if (!$updated) {

                $errors[] =
                    'Unable to update category.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | Rotate CSRF Token
                |--------------------------------------------------------------------------
                */

                regenerate_csrf_token();


                /*
                |--------------------------------------------------------------------------
                | Redirect
                |--------------------------------------------------------------------------
                */

                header(
                    'Location: categories.php',
                    true,
                    302
                );

                exit;
            }


        } catch (PDOException $e) {

            error_log(
                'Category update error: '
                . $e->getMessage()
            );

            $errors[] =
                'Unable to update category.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$pageTitle = 'Edit Category';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>


<main class="dashboard-main">

    <div class="content-card">


        <!-- Header -->

        <div class="dashboard-header">

            <div>

                <h1>
                    Edit Category
                </h1>

                <p>
                    Update the name of your expense category.
                </p>

            </div>

        </div>


        <!-- Errors -->

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


        <!-- Edit Form -->

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


            <div class="category-form">

                <div>

                    <label for="name">
                        Category Name
                    </label>


                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e($name) ?>"
                        maxlength="50"
                        autocomplete="off"
                        required
                        autofocus
                    >

                </div>


                <button
                    type="submit"
                    class="button"
                >
                    Update Category
                </button>

            </div>

        </form>


        <!-- Back -->

        <p style="margin-top:20px;">

            <a href="categories.php">
                ← Back to Categories
            </a>

        </p>


    </div>

</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>