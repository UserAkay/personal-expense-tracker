<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| Escape HTML Output
|--------------------------------------------------------------------------
|
| Safely escapes user/database data before displaying it in HTML.
|
*/

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
|
| Redirects the browser and immediately stops script execution.
|
*/

function redirect(string $url): never
{
    if (
        headers_sent()
    ) {
        exit;
    }

    header(
        'Location: ' . $url,
        true,
        302
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
|
| Creates one CSRF token per session.
|
*/

function csrf_token(): string
{
    if (
        !isset($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
        || strlen($_SESSION['csrf_token']) !== 64
    ) {

        $_SESSION['csrf_token'] =
            bin2hex(
                random_bytes(32)
            );
    }

    return $_SESSION['csrf_token'];
}


/*
|--------------------------------------------------------------------------
| Verify CSRF Token
|--------------------------------------------------------------------------
|
| Uses hash_equals() to prevent timing attacks.
|
*/

function verify_csrf_token(
    string $token
): bool {

    if (
        !isset($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
        || $token === ''
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['csrf_token'],
        $token
    );
}


/*
|--------------------------------------------------------------------------
| Regenerate CSRF Token
|--------------------------------------------------------------------------
|
| Generates a completely new token after sensitive operations.
|
*/

function regenerate_csrf_token(): string
{
    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );

    return $_SESSION['csrf_token'];
}


/*
|--------------------------------------------------------------------------
| Flash Message
|--------------------------------------------------------------------------
|
| Stores a temporary success/error message in the session.
|
*/

function set_flash(
    string $type,
    string $message
): void {

    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}


/*
|--------------------------------------------------------------------------
| Get Flash Message
|--------------------------------------------------------------------------
|
| Returns the flash message and removes it from the session.
|
*/

function get_flash(): ?array
{
    if (
        !isset($_SESSION['flash'])
        || !is_array($_SESSION['flash'])
    ) {
        return null;
    }

    $flash =
        $_SESSION['flash'];

    unset(
        $_SESSION['flash']
    );

    return $flash;
}