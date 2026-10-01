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

$success = '';

$name = '';


/*
|--------------------------------------------------------------------------
| Handle POST
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
            'Invalid security token. Please refresh the page and try again.';
    }


    /*
    |--------------------------------------------------------------------------
    | Action
    |--------------------------------------------------------------------------
    */

    $action =
        is_string($_POST['action'] ?? null)
            ? $_POST['action']
            : '';


    /*
    |--------------------------------------------------------------------------
    | Create Category
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors)
        && $action === 'create'
    ) {

        $name =
            trim(
                is_string($_POST['name'] ?? null)
                    ? $_POST['name']
                    : ''
            );


        /*
        |--------------------------------------------------------------------------
        | Validate Name
        |--------------------------------------------------------------------------
        */

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
                $userId
            )
        ) {

            $errors[] =
                'This category already exists.';

        }


        /*
        |--------------------------------------------------------------------------
        | Create
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                $categoryModel->create(
                    $userId,
                    $name
                );


                $success =
                    'Category created successfully.';

                $name = '';

                /*
                |--------------------------------------------------------------------------
                | Rotate CSRF Token
                |--------------------------------------------------------------------------
                */

                regenerate_csrf_token();


            } catch (PDOException $e) {

                error_log(
                    'Category creation error: '
                    . $e->getMessage()
                );

                $errors[] =
                    'Unable to create category.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Category
    |--------------------------------------------------------------------------
    */

    elseif (
        empty($errors)
        && $action === 'delete'
    ) {

        $categoryId =
            filter_var(
                $_POST['category_id'] ?? null,
                FILTER_VALIDATE_INT
            );


        if (
            $categoryId === false
            || $categoryId <= 0
        ) {

            $errors[] =
                'Invalid category.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Ownership Check
            |--------------------------------------------------------------------------
            */

            $category =
                $categoryModel->findById(
                    (int) $categoryId,
                    $userId
                );


            if ($category === null) {

                $errors[] =
                    'Category not found.';

             } elseif (
    $categoryModel->isUsed(
        (int) $categoryId,
        $userId
    )
) {

    $errors[] =
        'This category cannot be deleted because it is being used by an expense or budget.';
            } else {

                try {

                    $deleted =
                        $categoryModel->delete(
                            (int) $categoryId,
                            $userId
                        );


                    if ($deleted) {

                        $success =
                            'Category deleted successfully.';

                        regenerate_csrf_token();

                    } else {

                        $errors[] =
                            'Unable to delete category.';
                    }


                } catch (PDOException $e) {

                    error_log(
                        'Category deletion error: '
                        . $e->getMessage()
                    );

                    $errors[] =
                        'Unable to delete category.';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Load Categories
|--------------------------------------------------------------------------
*/

try {

    $categories =
        $categoryModel->getAllByUser(
            $userId
        );

} catch (PDOException $e) {

    error_log(
        'Category loading error: '
        . $e->getMessage()
    );

    $categories = [];

    $errors[] =
        'Unable to load categories.';
}


/*
|--------------------------------------------------------------------------
| Page Layout
|--------------------------------------------------------------------------
*/

$pageTitle = 'Categories';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>


<main class="dashboard-main">

    <div class="content-card">


        <!-- Page Header -->

        <div class="dashboard-header">

            <div>

                <h1>
                    Categories
                </h1>

                <p>
                    Organize your expenses by category.
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


        <!-- Success -->

        <?php if ($success !== ''): ?>

            <div
                class="success-message"
                role="status"
            >

                <?= e($success) ?>

            </div>

        <?php endif; ?>


        <!-- Add Category -->

        <div class="dashboard-section">

            <h2>
                Add Category
            </h2>


            <form
                method="POST"
                class="category-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >


                <input
                    type="hidden"
                    name="action"
                    value="create"
                >


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
                        placeholder="e.g. Food"
                        autocomplete="off"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="button"
                >
                    Add Category
                </button>

            </form>

        </div>


        <!-- Category List -->

        <div class="dashboard-section">

            <div class="section-header">

                <div>

                    <h2>
                        Your Categories
                    </h2>

                    <p>

                        <?= count($categories) ?>

                        categor<?= count($categories) === 1
                            ? 'y'
                            : 'ies' ?>

                    </p>

                </div>

            </div>


            <?php if (empty($categories)): ?>

                <div class="empty-state">

                    <h3>
                        No categories yet
                    </h3>

                    <p>
                        Create your first category above.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Created
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $categories
                                as $category
                            ): ?>

                                <tr>

                                    <td>
                                        <?= e(
                                            $category['name']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= e(
                                            $category['created_at']
                                        ) ?>
                                    </td>


                                    <td>

                                        <a
                                            href="edit_category.php?id=<?= (int) $category['id'] ?>"
                                        >
                                            Edit
                                        </a>

                                        |


                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="return confirm('Delete this category?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e(csrf_token()) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete"
                                            >


                                            <input
                                                type="hidden"
                                                name="category_id"
                                                value="<?= (int) $category['id'] ?>"
                                            >


                                            <button
                                                type="submit"
                                                style="border:0; background:none; padding:0; cursor:pointer;"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>