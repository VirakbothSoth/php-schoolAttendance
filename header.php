<?php
$page_title = $page_title ?? 'School Attendance System';
$is_staff_page = ($_SESSION['role'] ?? '') === 'staff';
$is_student_page = ($_SESSION['role'] ?? '') === 'student';
$current_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape_html($page_title) ?> - School Attendance System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Clarity+City:ital,wght@0,400..900;1,400..900&family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="<?= escape_html(app_base_path()) ?>/style.css?v=2.0" rel="stylesheet">

    <style>
        h1, h2, h3 {
            font-family: "Clarity City", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            font-family: 'Noto Sans', 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800 antialiased font-sans">
    <header class="bg-slate-900 border-b border-slate-800 text-white sticky top-0 z-30">
        <div class="mx-auto flex h-14 max-w-screen-2xl items-center justify-between px-4 sm:px-6">
            <a
                class="flex items-center text-lg font-semibold  text-white hover:text-slate-200 transition-colors"
                href="<?= escape_html(app_base_path() . ($is_staff_page ? '/staff/students.php' : '/student/overview.php')) ?>"
            >
                <span>School Attendance System</span>
            </a>
            <?php if (!empty($_SESSION['impersonator_staff_id'])): ?>
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center rounded-md bg-amber-500/15 px-2.5 py-1 text-xs font-medium text-amber-300 border border-amber-500/30">
                        Student Demo View
                    </span>
                    <a class="rounded-md bg-amber-600 px-3 py-2 text-md font-medium text-white hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-colors" href="<?= escape_html(app_base_path()) ?>/public/exit_demo.php">
                        Exit Demo
                    </a>
                </div>
            <?php elseif (!empty($_SESSION['user_id'])): ?>
                <a class="rounded-md border border-slate-700 px-3 py-2 text-md font-medium text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-600 transition-colors" href="<?= escape_html(app_base_path()) ?>/public/logout.php">
                    Sign Out
                </a>
            <?php endif; ?>
        </div>
    </header>
<main class="flex-1">
    <div class="flex min-h-[calc(100vh-3.5rem)] w-full flex-col lg:flex-row">
        <?php if ($is_staff_page || $is_student_page): ?>
            <aside class="bg-white border-r border-slate-200 px-3 py-4 lg:w-56 lg:shrink-0 lg:px-4 lg:py-6" aria-label="Main navigation">
                <nav class="flex flex-wrap gap-1 lg:flex-col">
                    <?php if ($is_staff_page): ?>
                        <a class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors <?= $current_page === 'students.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>" href="<?= escape_html(app_base_path()) ?>/staff/students.php"<?= $current_page === 'students.php' ? ' aria-current="page"' : '' ?>>
                            <svg class="h-4 w-4 shrink-0 <?= $current_page === 'students.php' ? 'text-blue-700' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 100 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Manage Students</span>
                        </a>
                        <a class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors <?= $current_page === 'attendance.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>" href="<?= escape_html(app_base_path()) ?>/staff/attendance.php"<?= $current_page === 'attendance.php' ? ' aria-current="page"' : '' ?>>
                            <svg class="h-4 w-4 shrink-0 <?= $current_page === 'attendance.php' ? 'text-blue-700' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Attendance</span>
                        </a>
                        <a class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors <?= $current_page === 'subjects.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>" href="<?= escape_html(app_base_path()) ?>/staff/subjects.php"<?= $current_page === 'subjects.php' ? ' aria-current="page"' : '' ?>>
                            <svg class="h-4 w-4 shrink-0 <?= $current_page === 'subjects.php' ? 'text-blue-700' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.25c-2.5-2-5.25-2-8-1v13.5c2.75-1 5.5-1 8 1m0-13.5c2.5-2 5.25-2 8-1v13.5c-2.75-1-5.5-1-8 1m0-13.5v13.5"/></svg>
                            <span>Subjects & Scores</span>
                        </a>
                    <?php else: ?>
                        <a class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors <?= ($current_page === 'overview.php' || $current_page === 'student.php') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>" href="<?= escape_html(app_base_path()) ?>/student/overview.php"<?= ($current_page === 'overview.php' || $current_page === 'student.php') ? ' aria-current="page"' : '' ?>>
                            <svg class="h-4 w-4 shrink-0 <?= ($current_page === 'overview.php' || $current_page === 'student.php') ? 'text-blue-700' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            <span>Overview</span>
                        </a>
                        <a class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors <?= ($current_page === 'attendance.php' || $current_page === 'student_attendance.php') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>" href="<?= escape_html(app_base_path()) ?>/student/attendance.php"<?= ($current_page === 'attendance.php' || $current_page === 'student_attendance.php') ? ' aria-current="page"' : '' ?>>
                            <svg class="h-4 w-4 shrink-0 <?= ($current_page === 'attendance.php' || $current_page === 'student_attendance.php') ? 'text-blue-700' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Attendance History</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </aside>
            <section class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
        <?php else: ?>
            <section class="w-full p-0">
        <?php endif; ?>
