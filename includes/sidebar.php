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

    <nav>

        <ul class="sidebar-menu">


            <!-- Dashboard -->

            <li>

                <a
                    href="dashboard.php"
                    class="<?= $currentPage === 'dashboard.php'
                        ? 'active'
                        : '' ?>"
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
                        style="
                            width:100%;
                            padding:12px 15px;
                            border:0;
                            background:transparent;
                            text-align:left;
                            color:#444;
                            cursor:pointer;
                            border-radius:6px;
                            font:inherit;
                        "
                    >
                        Logout
                    </button>

                </form>

            </li>


        </ul>

    </nav>

</aside>