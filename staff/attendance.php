<?php
    require_once __DIR__ . '/../auth.php';
    require_role('staff');
    require_once __DIR__ . '/../config.php';

    $selected_date = $_GET['date'] ?? date('Y-m-d');
    $date_object = DateTime::createFromFormat('!Y-m-d', $selected_date);
    if (!$date_object || $date_object->format('Y-m-d') !== $selected_date) {
        $selected_date = date('Y-m-d');
    }

    $error_message = '';
    $saved = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $submitted_date = $_POST['attendance_date'] ?? '';
        $submitted_date_object = DateTime::createFromFormat('!Y-m-d', $submitted_date);
        $statuses = $_POST['status'] ?? [];

        if (!$submitted_date_object || $submitted_date_object->format('Y-m-d') !== $submitted_date || !is_array($statuses)) {
            $error_message = 'Choose a valid date and attendance status for each student.';
        } else {
            $student_result = mysqli_query($link, "select id from users where role = 'student'");
            $student_ids = [];

            while ($row = mysqli_fetch_assoc($student_result)) {
                $student_ids[] = (int) $row['id'];
            }

            mysqli_begin_transaction($link);
            $statement = mysqli_prepare($link, 'insert into attendance (student_id, attendance_date, status) values (?, ?, ?) on duplicate key update status = values(status)');
            $valid_submission = true;

            foreach ($student_ids as $student_id) {
                $status = $statuses[(string) $student_id] ?? '';
                if (!in_array($status, ['attended', 'absent'], true)) {
                    $valid_submission = false;
                    break;
                }
                mysqli_stmt_bind_param($statement, 'iss', $student_id, $submitted_date, $status);
                if (!mysqli_stmt_execute($statement)) {
                    $valid_submission = false;
                    break;
                }
            }

            mysqli_stmt_close($statement);

            if ($valid_submission) {
                mysqli_commit($link);
                header('Location: ' . app_base_path() . '/staff/attendance.php?date=' . urlencode($submitted_date) . '&saved=1');
                exit;
            }

            mysqli_rollback($link);
            $error_message = 'Attendance was not saved. Review the student statuses and try again.';
        }

        $selected_date = $submitted_date;
    }

    $statement = mysqli_prepare($link, "select u.id, u.full_name, a.status from users u left join attendance a on a.student_id = u.id and a.attendance_date = ? where u.role = 'student' order by u.full_name");
    mysqli_stmt_bind_param($statement, 's', $selected_date);
    mysqli_stmt_execute($statement);

    $students = mysqli_stmt_get_result($statement);
    $page_title = 'Attendance';
    require __DIR__ . '/../header.php';
?>

<h1 class="mb-6 text-2xl font-semibold tracking-tight text-slate-900">Attendance</h1>

<?php if (isset($_GET['saved'])): ?>
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        Attendance saved for <?= escape_html($selected_date) ?>.
    </div>
<?php endif; ?>

<?php if ($error_message !== ''): ?>
    <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><?= escape_html($error_message) ?></div>
<?php endif; ?>

<form method="get" class="mb-5 flex flex-wrap items-end gap-3">
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700" for="date">Select date</label>
        <input class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" type="date" id="date" name="date" value="<?= escape_html($selected_date) ?>" required>
    </div>
    <div>
        <button class="rounded-md border border-blue-800 px-4 py-2 text-sm font-medium text-blue-900 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-600" type="submit">Load date</button>
    </div>
</form>

<form method="post">
    <input type="hidden" name="attendance_date" value="<?= escape_html($selected_date) ?>">
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Student</th>
                        <th class="w-72 px-5 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (mysqli_num_rows($students) === 0): ?>
                        <tr>
                            <td colspan="2" class="px-5 py-4 text-slate-500">Add student accounts before recording attendance.</td>
                        </tr>
                    <?php else: while ($student = mysqli_fetch_assoc($students)): ?>
                        <tr class="odd:bg-white even:bg-slate-50">
                            <td class="px-5 py-3"><?= escape_html($student['full_name']) ?></td>
                            <td class="px-5 py-3">
                                <select class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" name="status[<?= (int) $student['id'] ?>]" required>
                                <option value="attended" <?= $student['status'] === 'attended' ? 'selected' : '' ?>>
                                    Attended
                                </option>
                                <option value="absent" <?= $student['status'] === 'absent' ? 'selected' : '' ?>>
                                    Absent
                                </option>
                            </select></td>
                        </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (mysqli_num_rows($students) > 0): ?>
        <button class="mt-4 rounded-md bg-blue-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2" type="submit">Save attendance</button>
    <?php endif; ?>
</form>

<?php mysqli_stmt_close($statement); require __DIR__ . '/../footer.php'; ?>
