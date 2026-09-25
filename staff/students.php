<?php
require_once __DIR__ . '/../auth.php';
require_role('staff');
require_once __DIR__ . '/../config.php';

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($full_name === '' || $username === '' || strlen($password) < 8) {
        $error_message = 'Enter a name, username, and password of at least 8 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        mysqli_begin_transaction($link);
        $statement = mysqli_prepare(
            $link,
            "insert into users (full_name, username, password, role) values (?, ?, ?, 'student')"
        );
        mysqli_stmt_bind_param($statement, 'sss', $full_name, $username, $hash);
        if (mysqli_stmt_execute($statement)) {
            $student_id = mysqli_insert_id($link);
            $score_statement = mysqli_prepare(
                $link,
                'insert into student_subject_scores (student_id, subject_id, score) select ?, id, default_score from subjects'
            );
            mysqli_stmt_bind_param($score_statement, 'i', $student_id);
            $student_added = mysqli_stmt_execute($score_statement);
            mysqli_stmt_close($score_statement);
            mysqli_stmt_close($statement);

            if ($student_added) {
                mysqli_commit($link);
                header('Location: ' . app_base_path() . '/staff/students.php?added=1');
                exit;
            }

            mysqli_rollback($link);
            $error_message = 'The student could not be added.';
        } else {
            mysqli_rollback($link);
            $error_message = mysqli_errno($link) === 1062
                ? 'That username is already in use.'
                : 'The student could not be added.';
            mysqli_stmt_close($statement);
        }
    }
}
$students = mysqli_query(
    $link,
    "select id, full_name, username from users where role = 'student' order by full_name"
);
$page_title = 'Manage students';
require __DIR__ . '/../header.php';
?>
<h1 class="mb-6 text-2xl font-semibold tracking-tight text-slate-900">Manage students</h1>
<?php if (isset($_GET['added'])): ?>
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Student account created.</div>
<?php endif; ?>
<?php if ($error_message !== ''): ?>
    <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><?= escape_html($error_message) ?></div>
<?php endif; ?>
<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white  ">
            <div class="p-5">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Add student</h2>
                <form method="post">
                    <div class="mb-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="full_name">Full name</label>
                        <input class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm   focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" id="full_name" name="full_name" maxlength="120" required>
                    </div>
                    <div class="mb-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="username">Username</label>
                        <input class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm   focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" id="username" name="username" maxlength="80" required>
                    </div>
                    <div class="mb-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="password">Password</label>
                        <input
                            class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm   focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            type="password"
                            id="password"
                            name="password"
                            minlength="8"
                            required
                        >
                    </div>
                    <button class="rounded-md bg-blue-800 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2" type="submit">Create student</button>
                </form>
            </div>
        </div>
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white   lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Name</th>
                            <th class="px-5 py-3 font-semibold">Username</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (mysqli_num_rows($students) === 0): ?>
                            <tr>
                                <td colspan="2" class="px-5 py-4 text-slate-500">No students yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php while ($student = mysqli_fetch_assoc($students)): ?>
                                <tr class="odd:bg-white even:bg-slate-50">
                                    <td class="px-5 py-3"><?= escape_html($student['full_name']) ?></td>
                                    <td class="px-5 py-3"><?= escape_html($student['username']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
</div>
<?php
require __DIR__ . '/../footer.php';
?>
