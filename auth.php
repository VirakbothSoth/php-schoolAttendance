<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function require_role(string $role): void
{
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== $role) {
        header('Location: ' . app_base_path() . '/index.php');
        exit;
    }
}

function app_base_path(): string
{
    $script_directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (basename($script_directory) === 'staff') {
        $script_directory = dirname($script_directory);
    }

    return rtrim($script_directory, '/');
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
