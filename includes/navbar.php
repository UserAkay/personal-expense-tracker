<?php

declare(strict_types=1);

?>

<header class="top-navbar">

    <div class="navbar-brand">

        <a href="dashboard.php">
            ExpenseFlow
        </a>

    </div>


    <div class="navbar-user">

        <span>
            Welcome,
            <strong>
                <?= htmlspecialchars(
                    $_SESSION['user_name'] ?? 'User',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>
        </span>


        <a
            href="profile.php"
            class="profile-link"
        >
            Profile
        </a>

    </div>

</header>