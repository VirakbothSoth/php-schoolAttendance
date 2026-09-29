<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';

if (!empty($_SESSION['user_id'])) {
    $destination = $_SESSION['role'] === 'staff' ? '/staff/students.php' : '/student/overview.php';
    header('Location: ' . app_base_path() . $destination);
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error_message = 'Please enter both your username and password.';
    } else {
        $statement = mysqli_prepare($link, 'select id, password, role from users where username = ?');
        mysqli_stmt_bind_param($statement, 's', $username);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($statement);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['role'] = $user['role'];
            $destination = $user['role'] === 'staff' ? '/staff/students.php' : '/student/overview.php';
            header('Location: ' . app_base_path() . $destination);
            exit;
        }
        $error_message = 'Invalid username or password.';
    }
}

$page_title = 'Sign In';
require __DIR__ . '/../header.php';
?>
<div class="relative flex min-h-[calc(100vh-3.5rem)] w-full items-center justify-center bg-slate-900 bg-cover bg-center px-4 py-12 sm:px-6 lg:px-8" style="background-image: linear-gradient(to bottom, rgba(15, 23, 42, 0.65), rgba(15, 23, 42, 0.85)), url('<?= escape_html(app_base_path()) ?>/public/bg-login.jpg');">
    <div class="w-full max-w-md space-y-6">
        <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-2xl">
            <div class="mb-6">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Sign in to your account</h1>
                <p class="mt-1 text-sm text-slate-500">Access the School Attendance System</p>
            </div>

            <?php if ($error_message !== ''): ?>
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-3.5 text-sm text-red-700" role="alert">
                    <div class="flex items-center gap-2 font-medium">
                        <svg class="h-4 w-4 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><?= escape_html($error_message) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= escape_html(app_base_path()) ?>/public/index.php" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="username">Username</label>
                    <input
                        class="block w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 transition-colors"
                        id="username"
                        name="username"
                        type="text"
                        required
                        maxlength="80"
                        value="<?= escape_html($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                        placeholder="Enter your username"
                        autofocus
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="password">Password</label>
                    <input
                        class="block w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 transition-colors"
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    >
                </div>

                <button
                    class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-colors cursor-pointer shadow-xs"
                    type="submit"
                >
                    Sign In
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 text-xs text-slate-500 text-center">
                If you cannot log in or need an account, please contact school staff.
            </div>
        </div>
    </div>
</div>
<?php
require __DIR__ . '/../footer.php';
?>
