<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function require_role(string $role): void
{
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== $role) {
        header('Location: ' . app_base_path() . '/public/index.php');
        exit;
    }
}

function app_base_path(): string
{
    $script_directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $folder_name = basename($script_directory);
    if (in_array($folder_name, ['staff', 'student', 'public'], true)) {
        $script_directory = dirname($script_directory);
    }

    return rtrim($script_directory, '/');
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
