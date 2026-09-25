<?php
$page_title = $page_title ?? 'School Attendance';
$is_staff_page = ($_SESSION['role'] ?? '') === 'staff';
$is_student_page = ($_SESSION['role'] ?? '') === 'student';
$current_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape_html($page_title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="<?= escape_html(app_base_path()) ?>/style.css?v=login-refresh1" rel="stylesheet">
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800 antialiased">
    <nav class="bg-blue-900 text-white shadow-sm">
        <div class="mx-auto flex h-14 max-w-screen-2xl items-center justify-between px-4 sm:px-6">
            <a
                class="text-lg font-semibold tracking-tight"
                href="<?= escape_html(app_base_path() . ($is_staff_page ? '/staff/students.php' : '/student.php')) ?>"
            >
                School Attendance
            </a>
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a class="rounded-md border border-white/60 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-white hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-white/70" href="<?= escape_html(app_base_path()) ?>/logout.php">
                    Sign out
                </a>
            <?php endif; ?>
        </div>
    </nav>
<main class="flex-1">
    <div class="flex min-h-[calc(100vh-3.5rem)] w-full flex-col lg:flex-row">
        <?php if ($is_staff_page || $is_student_page): ?>
            <aside class="bg-slate-200 px-4 py-3 lg:w-64 lg:shrink-0 lg:px-4 lg:py-6" aria-label="Main navigation">
                    <nav class="flex flex-wrap gap-1 lg:flex-col">
                        <?php if ($is_staff_page): ?>
                            <a class="rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-white/70 focus:outline-none focus:ring-2 focus:ring-blue-700 <?= $current_page === 'students.php' ? 'bg-white text-blue-900 shadow-sm' : '' ?>" href="<?= escape_html(app_base_path()) ?>/staff/students.php"<?= $current_page === 'students.php' ? ' aria-current="page"' : '' ?>>Manage students</a>
                            <a class="rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-white/70 focus:outline-none focus:ring-2 focus:ring-blue-700 <?= $current_page === 'attendance.php' ? 'bg-white text-blue-900 shadow-sm' : '' ?>" href="<?= escape_html(app_base_path()) ?>/staff/attendance.php"<?= $current_page === 'attendance.php' ? ' aria-current="page"' : '' ?>>Attendance</a>
                            <a class="rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-white/70 focus:outline-none focus:ring-2 focus:ring-blue-700 <?= $current_page === 'subjects.php' ? 'bg-white text-blue-900 shadow-sm' : '' ?>" href="<?= escape_html(app_base_path()) ?>/staff/subjects.php"<?= $current_page === 'subjects.php' ? ' aria-current="page"' : '' ?>>Subjects</a>
                        <?php else: ?>
                            <a class="rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-white/70 focus:outline-none focus:ring-2 focus:ring-blue-700 <?= $current_page === 'student.php' ? 'bg-white text-blue-900 shadow-sm' : '' ?>" href="<?= escape_html(app_base_path()) ?>/student.php"<?= $current_page === 'student.php' ? ' aria-current="page"' : '' ?>>Overview</a>
                            <a class="rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-white/70 focus:outline-none focus:ring-2 focus:ring-blue-700 <?= $current_page === 'student_attendance.php' ? 'bg-white text-blue-900 shadow-sm' : '' ?>" href="<?= escape_html(app_base_path()) ?>/student_attendance.php"<?= $current_page === 'student_attendance.php' ? ' aria-current="page"' : '' ?>>Attendance history</a>
                        <?php endif; ?>
                    </nav>
            </aside>
            <section class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
        <?php else: ?>
            <section class="w-full p-0">
        <?php endif; ?>
