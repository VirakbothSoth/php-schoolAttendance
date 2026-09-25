<?php
require_once __DIR__ . '/../auth.php';
require_role('staff');
require_once __DIR__ . '/../config.php';

function is_valid_score(string $score): bool
{
    return preg_match('/^\d{1,5}(?:\.\d{1,2})?$/', $score) === 1;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_subject') {
        $name = trim($_POST['name'] ?? '');
        $default_score = trim($_POST['default_score'] ?? '');
        $max_score = trim($_POST['max_score'] ?? '');

        if ($name === '' || strlen($name) > 120) {
            $error_message = 'Enter a subject name of up to 120 characters.';
        } elseif (!is_valid_score($default_score) || !is_valid_score($max_score)) {
            $error_message = 'Enter a valid default score and maximum score.';
        } elseif (
            (float) $max_score < 0
            || (float) $max_score > 99999.99
            || (float) $default_score < 0
            || (float) $default_score > (float) $max_score
        ) {
            $error_message = 'Scores must be between 0 and 99,999.99, and the default cannot exceed the maximum.';
        } else {
            mysqli_begin_transaction($link);
            $subject_statement = mysqli_prepare(
                $link,
                'insert into subjects (name, default_score, max_score) values (?, ?, ?)'
            );
            mysqli_stmt_bind_param($subject_statement, 'sss', $name, $default_score, $max_score);
            $subject_saved = mysqli_stmt_execute($subject_statement);

            if ($subject_saved) {
                $subject_id = mysqli_insert_id($link);
                $score_statement = mysqli_prepare(
                    $link,
                    'insert into student_subject_scores (student_id, subject_id, score) select id, ?, ? from users where role = \'student\''
                );
                mysqli_stmt_bind_param($score_statement, 'is', $subject_id, $default_score);
                $subject_saved = mysqli_stmt_execute($score_statement);
                mysqli_stmt_close($score_statement);
            }

            mysqli_stmt_close($subject_statement);
            $save_error = mysqli_errno($link);

            if ($subject_saved) {
                mysqli_commit($link);
                $success_message = 'Subject added and assigned to all students.';
            } else {
                mysqli_rollback($link);
                $error_message = $save_error === 1062
                    ? 'A subject with that name already exists.'
                    : 'The subject could not be added.';
            }
        }
    } elseif ($action === 'update_scores') {
        $subject_id = filter_var($_POST['subject_id'] ?? null, FILTER_VALIDATE_INT);
        $scores = $_POST['scores'] ?? [];

        $limit_statement = mysqli_prepare($link, 'select max_score from subjects where id = ?');
        mysqli_stmt_bind_param($limit_statement, 'i', $subject_id);
        mysqli_stmt_execute($limit_statement);
        $subject_limit = mysqli_fetch_assoc(mysqli_stmt_get_result($limit_statement));
        mysqli_stmt_close($limit_statement);

        if (!$subject_id || !$subject_limit || !is_array($scores)) {
            $error_message = 'Enter valid student scores.';
        } else {
            $update_statement = mysqli_prepare(
                $link,
                'update student_subject_scores set score = ? where student_id = ? and subject_id = ?'
            );
            $all_saved = true;

            foreach ($scores as $student_id => $score) {
                $student_id = filter_var($student_id, FILTER_VALIDATE_INT);
                $score = trim($score);

                if (!$student_id || !is_valid_score($score) || (float) $score > (float) $subject_limit['max_score']) {
                    $all_saved = false;
                    continue;
                }

                mysqli_stmt_bind_param($update_statement, 'sii', $score, $student_id, $subject_id);
                if (!mysqli_stmt_execute($update_statement)) {
                    $all_saved = false;
                }
            }
            mysqli_stmt_close($update_statement);

            $success_message = $all_saved ? 'Scores updated.' : '';
            $error_message = $all_saved ? '' : 'One or more scores were invalid and were not saved.';
        }
    }
}

$subjects = mysqli_query($link, 'select id, name, default_score, max_score from subjects order by name');
$page_title = 'Subjects';
require __DIR__ . '/../header.php';
?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-1 text-2xl font-semibold tracking-tight text-slate-900">Subjects</h1>
        <p class="text-sm text-slate-600">
            Set up subjects and manage each student’s score.
        </p>
    </div>
</div>

<?php if ($success_message !== ''): ?>
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status"><?= escape_html($success_message) ?></div>
<?php endif; ?>
<?php if ($error_message !== ''): ?>
    <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert"><?= escape_html($error_message) ?></div>
<?php endif; ?>

<div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Add a subject</h2>
        <form method="post" class="grid items-end gap-4 md:grid-cols-2 xl:grid-cols-4">
            <input type="hidden" name="action" value="create_subject">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="name">Subject name</label>
                <input class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" id="name" name="name" maxlength="120" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="default_score">Default score</label>
                <input
                    class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    type="number"
                    id="default_score"
                    name="default_score"
                    min="0"
                    max="99999.99"
                    step="0.01"
                    required
                >
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="max_score">Maximum score</label>
                <input
                    class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    type="number"
                    id="max_score"
                    name="max_score"
                    min="0"
                    max="99999.99"
                    step="0.01"
                    required
                >
            </div>
            <div>
                <button class="rounded-md bg-blue-800 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2" type="submit">Add</button>
            </div>
        </form>
</div>

<?php if (mysqli_num_rows($subjects) === 0): ?>
    <div class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-sm">No subjects have been added yet.</div>
<?php else: ?>
    <?php while ($subject = mysqli_fetch_assoc($subjects)): ?>
        <?php
        $score_statement = mysqli_prepare(
            $link,
            'select u.id, u.full_name, ss.score '
                . 'from users u join student_subject_scores ss on ss.student_id = u.id '
                . 'where u.role = \'student\' and ss.subject_id = ? order by u.full_name'
        );
        mysqli_stmt_bind_param($score_statement, 'i', $subject['id']);
        mysqli_stmt_execute($score_statement);
        $student_scores = mysqli_stmt_get_result($score_statement);
        ?>
        <section class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="subject-<?= (int) $subject['id'] ?>">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-4">
                <h2 class="text-lg font-semibold text-slate-900" id="subject-<?= (int) $subject['id'] ?>"><?= escape_html($subject['name']) ?></h2>
                <span class="text-sm text-slate-500">
                    Default <?= escape_html((string) $subject['default_score']) ?>
                    · Max <?= escape_html((string) $subject['max_score']) ?>
                </span>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="update_scores">
                <input type="hidden" name="subject_id" value="<?= (int) $subject['id'] ?>">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Student</th>
                                <th class="px-5 py-3 font-semibold">Score</th>
                                <th class="px-5 py-3 font-semibold">Update score</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (mysqli_num_rows($student_scores) === 0): ?>
                                <tr>
                                    <td colspan="3" class="px-5 py-4 text-slate-500">No students yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($student = mysqli_fetch_assoc($student_scores)): ?>
                                    <tr class="odd:bg-white even:bg-slate-50">
                                        <td class="px-5 py-3"><?= escape_html($student['full_name']) ?></td>
                                        <td class="px-5 py-3">
                                            <?= escape_html((string) $student['score']) ?>
                                            / <?= escape_html((string) $subject['max_score']) ?>
                                        </td>
                                        <td class="px-5 py-3">
                                            <input
                                                class="min-w-0 w-full max-w-40 rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                                type="number"
                                                name="scores[<?= (int) $student['id'] ?>]"
                                                value="<?= escape_html((string) $student['score']) ?>"
                                                min="0"
                                                max="<?= escape_html((string) $subject['max_score']) ?>"
                                                step="0.01"
                                                aria-label="Score for <?= escape_html($student['full_name']) ?>"
                                                required
                                            >
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end border-t border-slate-200 px-5 py-3">
                    <button class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-blue-500 border-2 border-sky-500  hover:bg-grey-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2" type="submit">Save</button>
                </div>
            </form>
        </section>
        <?php mysqli_stmt_close($score_statement); ?>
    <?php endwhile; ?>
<?php endif; ?>
<?php
require __DIR__ . '/../footer.php';
?>