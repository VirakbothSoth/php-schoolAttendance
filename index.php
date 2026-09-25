<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['user_id'])) {
    $destination = $_SESSION['role'] === 'staff' ? '/staff/students.php' : '/student.php';
    header('Location: ' . app_base_path() . $destination);
    exit;
}

$error_message = '';
$learning_quotes = [
    'Every page you turn is a step toward who you can become.',
    'Small steps in learning lead to remarkable journeys.',
    'Curiosity opens doors that effort can walk through.',
    'The future belongs to those who keep asking, learning, and growing.',
    'Knowledge grows each time you choose to begin again.',
    'Its better to improve 1% every day than 20% every month.',
    'One day, or day one.'
];
$learning_quote = $learning_quotes[array_rand($learning_quotes)];

$welcome = [
    'Good day to you!',
    'Lets get going!',
    'Greetings!',
    'Welcome!',
    'Hello!',
    'Suostei!',
];
$welcome = $welcome[array_rand($welcome)];

$signinmessage = [
    'Sign in to see your progress!',
    'Sign in to continue!',
];
$signinmessage = $signinmessage[array_rand($signinmessage)];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error_message = 'Enter your username and password.';
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
            $destination = $user['role'] === 'staff' ? '/staff/students.php' : '/student.php';
            header('Location: ' . app_base_path() . $destination);
            exit;
        }
        $error_message = 'The username or password is incorrect.';
    }
}

$page_title = 'Sign in';
require __DIR__ . '/header.php';
?>
<div class="login-layout grid min-h-[calc(100vh-3.5rem)] grid-cols-1 lg:grid-cols-[60%_40%]">
    <div class="flex items-center justify-center px-4 py-8 sm:px-8 lg:px-12">
    <div class="login-card w-full max-w-md rounded-2xl border border-slate-200/80 bg-white/95 shadow-xl shadow-blue-950/5">
        <div class="p-7 sm:p-9">
            <div class="mb-7 text-center">
                <blockquote class="mx-auto max-w-sm font-serif text-lg leading-7 text-slate-700">&ldquo;<?= escape_html($learning_quote) ?>&rdquo;</blockquote>
            </div>
            <div class="mb-6 text-center">
                <h1 class="text-3xl font-bold tracking-tight text-slate-900"><?= escape_html($welcome) ?></h1>
                <p class="mt-2 text-sm leading-6 text-slate-600"><?= escape_html($signinmessage) ?></p>
            </div>
                <?php if ($error_message !== ''): ?>
                    <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                        <?= escape_html($error_message) ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="<?= escape_html(app_base_path()) ?>/index.php">
                    <div class="mb-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="username">Username</label>
                        <input
                            class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm   focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            id="username"
                            name="username"
                            required
                            maxlength="80"
                            autocomplete="username"
                        >
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="password">Password</label>
                        <input
                            class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm   focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                    <button class="w-full rounded-md bg-blue-800 px-4 py-2.5 my-4 text-sm font-semibold text-white   hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2" type="submit">Sign in</button>
                    <p class="text-neutral-400">Can't log in? Check your password, or you need to wait for a staff to register your account!</p>
                </form>
        </div>
    </div>
    </div>
    <aside class="login-art hidden items-center justify-center px-10 py-12 text-center text-white lg:flex" aria-label="Learning inspiration">
        <div class="relative z-10 max-w-sm">
            <span class="mb-5 inline-flex h-14 w-14 items-center justify-center rounded-2xl border border-white/20 bg-white/10" aria-hidden="true">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.25c-2.5-2-5.25-2-8-1v13.5c2.75-1 5.5-1 8 1m0-13.5c2.5-2 5.25-2 8-1v13.5c-2.75-1-5.5-1-8 1m0-13.5v13.5"/></svg>
            </span>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-200">Learn a little every day</p>
            <h2 class="mt-4 text-3xl font-bold leading-tight">Your next achievement starts with showing up.</h2>
            <p class="mt-4 text-sm leading-6 text-blue-100">Keep building your knowledge, one lesson and one day at a time.</p>
        </div>
    </aside>
</div>
<?php
require __DIR__ . '/footer.php';
?>
