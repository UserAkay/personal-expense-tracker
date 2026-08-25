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
| Page Title
|--------------------------------------------------------------------------
*/

$pageTitle =
    $pageTitle ?? 'Personal Expense Tracker';

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
        content="Personal Expense Tracker with analytics and budgeting."
    >

    <meta
        name="theme-color"
        content="#4f46e5"
    >

    <title>
        <?= htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
        | Personal Expense Tracker
    </title>


    <!-- Main Stylesheet -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- Dashboard Stylesheet -->

    <link
        rel="stylesheet"
        href="../assets/css/dashboard.css"
    >

</head>

<body>