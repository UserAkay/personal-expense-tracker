<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';


/*
|--------------------------------------------------------------------------
| Current Page
|--------------------------------------------------------------------------
*/

$currentPage =
    basename($_SERVER['PHP_SELF']);

?>

<aside class="sidebar">

    <nav aria-label="Main navigation">

        <ul class="sidebar-menu">


            <!-- Dashboard -->

            <li>

                <a
                    href="dashboard.php"
                    class="<?= $currentPage === 'dashboard.php'
                        ? 'active'
                        : '' ?>"
                    aria-current="<?= $currentPage === 'dashboard.php'
                        ? 'page'
                        : 'false' ?>"
                >
                    Dashboard
                </a>

            </li>


            <!-- Expenses -->

            <li>

                <a
                    href="expense.php"
                    class="<?= $currentPage === 'expense.php'
                        ? 'active'
                        : '' ?>"
                    aria-current="<?= $currentPage === 'expense.php'
                        ? 'page'
                        : 'false' ?>"
                >
                    Expenses
                </a>

            </li>


            <!-- Add Expense -->

            <li>

                <a
                    href="add_expense.php"
                    class="<?= $currentPage === 'add_expense.php'
                        ? 'active'
                        : '' ?>"
                    aria-current="<?= $currentPage === 'add_expense.php'
                        ? 'page'
                        : 'false' ?>"
                >
                    Add Expense
                </a>

            </li>


            <!-- Categories -->

            <li>

                <a
                    href="categories.php"
                    class="<?= $currentPage === 'categories.php'
                        ? 'active'
                        : '' ?>"
                    aria-current="<?= $currentPage === 'categories.php'
                        ? 'page'
                        : 'false' ?>"
                >
                    Categories
                </a>

            </li>


            <!-- Budgets -->

            <li>

                <a
                    href="budgets.php"
                    class="<?= $currentPage === 'budgets.php'
                        ? 'active'
                        : '' ?>"
                    aria-current="<?= $currentPage === 'budgets.php'
                        ? 'page'
                        : 'false' ?>"
                >
                    Budgets
                </a>

            </li>


            <!-- Analytics -->

            <li>

                <a
                    href="analytics.php"
                    class="<?= $currentPage === 'analytics.php'
                        ? 'active'
                        : '' ?>"
                    aria-current="<?= $currentPage === 'analytics.php'
                        ? 'page'
                        : 'false' ?>"
                >
                    Analytics
                </a>

            </li>


            <!-- Reports -->

            <li>

                <a
                    href="reports.php"
                    class="<?= $currentPage === 'reports.php'
                        ? 'active'
                        : '' ?>"
                    aria-current="<?= $currentPage === 'reports.php'
                        ? 'page'
                        : 'false' ?>"
                >
                    Reports
                </a>

            </li>


            <!-- Profile -->

            <li>

                <a
                    href="profile.php"
                    class="<?= $currentPage === 'profile.php'
                        ? 'active'
                        : '' ?>"
                    aria-current="<?= $currentPage === 'profile.php'
                        ? 'page'
                        : 'false' ?>"
                >
                    Profile
                </a>

            </li>


            <!-- Logout -->

            <li class="logout-item">

                <form
                    method="POST"
                    action="logout.php"
                    style="margin:0;"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrf_token()) ?>"
                    >

                    <button
                        type="submit"
                        aria-label="Logout"
                    >
                        Logout
                    </button>

                </form>

            </li>


        </ul>

    </nav>

</aside>