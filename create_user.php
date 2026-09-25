<?php
require_once __DIR__ . '/config.php';

$error_message = '';
$success_message = '';
$full_name = '';
$username = '';
$role = 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($full_name === '' || strlen($full_name) > 120) {
        $error_message = 'Enter a name of up to 120 characters.';
    } elseif ($username === '' || strlen($username) > 80) {
        $error_message = 'Enter a username of up to 80 characters.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters.';
    } elseif (!in_array($role, ['student', 'staff'], true)) {
        $error_message = 'Choose a valid account role.';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $statement = mysqli_prepare($link, 'insert into users (full_name, username, password, role) values (?, ?, ?, ?)');
        mysqli_stmt_bind_param($statement, 'ssss', $full_name, $username, $password_hash, $role);

        if (mysqli_stmt_execute($statement)) {
            $success_message = 'Account created successfully.';
            $full_name = '';
            $username = '';
            $role = 'student';
        } elseif (mysqli_errno($link) === 1062) {
            $error_message = 'That username is already in use.';
        } else {
            $error_message = 'Could not create the account.';
            error_log('User creation failed: ' . mysqli_error($link));
        }

        mysqli_stmt_close($statement);
    }
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create user</title>
</head>
<body>
    <h1>Create user</h1>
    <?php if ($error_message !== ''): ?>
        <p role="alert"><?= escape_html($error_message) ?></p>
    <?php endif; ?>
    <?php if ($success_message !== ''): ?>
        <p><?= escape_html($success_message) ?></p>
    <?php endif; ?>

    <form method="post" action="create_user.php">
        <p>
            <label for="full_name">Full name</label><br>
            <input
                type="text"
                id="full_name"
                name="full_name"
                maxlength="120"
                required
                value="<?= escape_html($full_name) ?>"
            >
        </p>
        <p>
            <label for="username">Username</label><br>
            <input
                type="text"
                id="username"
                name="username"
                maxlength="80"
                required
                value="<?= escape_html($username) ?>"
            >
        </p>
        <p>
            <label for="password">Password (at least 8 characters)</label><br>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
                autocomplete="new-password"
            >
        </p>
        <p>
            <label for="role">Role</label><br>
            <select id="role" name="role" required>
                <option value="student"<?= $role === 'student' ? ' selected' : '' ?>>Student</option>
                <option value="staff"<?= $role === 'staff' ? ' selected' : '' ?>>Staff</option>
            </select>
        </p>
        <button type="submit">Create account</button>
    </form>
</body>
</html>
