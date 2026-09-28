<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';

if (!empty($_SESSION['impersonator_staff_id'])) {
    $staff_id = (int) $_SESSION['impersonator_staff_id'];
    $stmt = mysqli_prepare($link, "select id, role from users where id = ? and role = 'staff'");
    mysqli_stmt_bind_param($stmt, 'i', $staff_id);
    mysqli_stmt_execute($stmt);
    $staff = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($staff) {
        unset($_SESSION['impersonator_staff_id']);
        $_SESSION['user_id'] = (int) $staff['id'];
        $_SESSION['role'] = 'staff';
        header('Location: ' . app_base_path() . '/staff/students.php');
        exit;
    }
}

// Fallback to logout
header('Location: ' . app_base_path() . '/public/logout.php');
exit;
