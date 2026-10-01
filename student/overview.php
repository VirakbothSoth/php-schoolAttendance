<?php
require_once __DIR__ . '/../auth.php';
require_role('student');
require_once __DIR__ . '/../config.php';

$statement = mysqli_prepare(
    $link,
    "select full_name, username, (select count(*) from attendance "
        . "where student_id = users.id and status = 'attended') as points "
        . "from users where id = ? and role = 'student'"
);
mysqli_stmt_bind_param($statement, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($statement);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));
mysqli_stmt_close($statement);
if (!$student) {
    http_response_code(403);
    exit('Student account is unavailable.');
}

$month_input = $_GET['month'] ?? date('Y-m');
$month_start = is_string($month_input) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month_input)
    ? DateTimeImmutable::createFromFormat('!Y-m-d', $month_input . '-01')
    : false;
if (!$month_start) {
    $month_start = new DateTimeImmutable('first day of this month');
}
$next_month = $month_start->modify('first day of next month');
$calendar_start = $month_start->modify('-' . $month_start->format('w') . ' days');
$previous_month = $month_start->modify('-1 month')->format('Y-m');
$following_month = $month_start->modify('+1 month')->format('Y-m');

$calendar_statement = mysqli_prepare(
    $link,
    'select attendance_date, status from attendance where student_id = ? and attendance_date >= ? and attendance_date < ?'
);
$month_first_day = $month_start->format('Y-m-d');
$next_month_first_day = $next_month->format('Y-m-d');
mysqli_stmt_bind_param($calendar_statement, 'iss', $_SESSION['user_id'], $month_first_day, $next_month_first_day);
mysqli_stmt_execute($calendar_statement);
$calendar_result = mysqli_stmt_get_result($calendar_statement);
$calendar_statuses = [];
while ($calendar_record = mysqli_fetch_assoc($calendar_result)) {
    $calendar_statuses[$calendar_record['attendance_date']] = $calendar_record['status'];
}
mysqli_stmt_close($calendar_statement);
$month_attendance_counts = array_count_values($calendar_statuses);

$selected_range = $_GET['range'] ?? 'monthly';
if (!in_array($selected_range, ['weekly', 'monthly', 'yearly', 'entirely'], true)) {
    $selected_range = 'monthly';
}

$today = new DateTimeImmutable();
switch ($selected_range) {
    case 'weekly':
        $dow = (int) $today->format('N');
        $week_start_dt = $today->modify('-' . ($dow - 1) . ' days');
        $range_start = $week_start_dt->format('Y-m-d');
        $range_end = $week_start_dt->modify('+7 days')->format('Y-m-d');
        $attended_title = 'Attended (This week)';
        $absent_title = 'Absent (This week)';
        $points_subtitle = 'Points earned this week';
        break;
    case 'yearly':
        $year_str = $today->format('Y');
        $range_start = $year_str . '-01-01';
        $range_end = ((int) $year_str + 1) . '-01-01';
        $attended_title = 'Attended in ' . $year_str;
        $absent_title = 'Absent in ' . $year_str;
        $points_subtitle = 'Points earned in ' . $year_str;
        break;
    case 'entirely':
        $range_start = null;
        $range_end = null;
        $attended_title = 'Attended (All time)';
        $absent_title = 'Absent (All time)';
        $points_subtitle = 'Total points earned';
        break;
    case 'monthly':
    default:
        $range_start = $month_start->format('Y-m-d');
        $range_end = $next_month->format('Y-m-d');
        $month_name = $month_start->format('F');
        $attended_title = 'Attended in ' . $month_name;
        $absent_title = 'Absent in ' . $month_name;
        $points_subtitle = 'Points earned in ' . $month_name;
        break;
}

if ($selected_range === 'entirely') {
    $stats_statement = mysqli_prepare(
        $link,
        "select status, count(*) as count from attendance where student_id = ? group by status"
    );
    mysqli_stmt_bind_param($stats_statement, 'i', $_SESSION['user_id']);
} else {
    $stats_statement = mysqli_prepare(
        $link,
        "select status, count(*) as count from attendance where student_id = ? and attendance_date >= ? and attendance_date < ? group by status"
    );
    mysqli_stmt_bind_param($stats_statement, 'iss', $_SESSION['user_id'], $range_start, $range_end);
}
mysqli_stmt_execute($stats_statement);
$stats_result = mysqli_stmt_get_result($stats_statement);
$range_attendance_counts = ['attended' => 0, 'absent' => 0];
while ($row = mysqli_fetch_assoc($stats_result)) {
    $range_attendance_counts[$row['status']] = (int) $row['count'];
}
mysqli_stmt_close($stats_statement);

$recent_attendance_statement = mysqli_prepare(
    $link,
    'select attendance_date, status from attendance where student_id = ? order by attendance_date desc limit 10'
);
mysqli_stmt_bind_param($recent_attendance_statement, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($recent_attendance_statement);
$recent_attendance = mysqli_stmt_get_result($recent_attendance_statement);

$score_statement = mysqli_prepare(
    $link,
    'select s.name, s.default_score, s.max_score, ss.score '
        . 'from student_subject_scores ss join subjects s on s.id = ss.subject_id '
        . 'where ss.student_id = ? order by s.name'
);
mysqli_stmt_bind_param($score_statement, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($score_statement);
$subject_scores = mysqli_stmt_get_result($score_statement);
$page_title = 'Student Overview';
require __DIR__ . '/../header.php';
?>

<div class="mb-5">
    <h1 class="text-2xl font-bold text-slate-900">Student Dashboard</h1>
    <p class="text-sm text-slate-500">Welcome back, <span class="font-semibold text-slate-700"><?= escape_html($student['full_name'] ?? $student['username']) ?></span>. Here is your academic progress.</p>
</div>

<div class="mb-5 flex items-center justify-start">
    <form method="get" class="flex items-center gap-2">
        <?php if (isset($_GET['month'])): ?>
            <input type="hidden" name="month" value="<?= escape_html($_GET['month']) ?>">
        <?php endif; ?>
        <label for="range" class="text-sm font-medium text-slate-700">Timeframe:</label>
        <select
            id="range"
            name="range"
            onchange="this.form.submit()"
            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
        >
            <option value="weekly"<?= $selected_range === 'weekly' ? ' selected' : '' ?>>Weekly</option>
            <option value="monthly"<?= $selected_range === 'monthly' ? ' selected' : '' ?>>Monthly</option>
            <option value="yearly"<?= $selected_range === 'yearly' ? ' selected' : '' ?>>Yearly</option>
            <option value="entirely"<?= $selected_range === 'entirely' ? ' selected' : '' ?>>All Time</option>
        </select>
    </form>
</div>

<div class="grid items-start gap-5 lg:grid-cols-12">
    <div class="space-y-5 lg:col-span-9">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-md text-slate-500">Attendance Points</div>
                <div class="mt-2 text-3xl font-bold text-slate-900"><?= (int) $range_attendance_counts['attended'] ?></div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-md text-slate-500"><?= escape_html($attended_title) ?></div>
                <div class="mt-2 text-3xl font-bold text-emerald-600"><?= (int) $range_attendance_counts['attended'] ?></div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-md text-slate-500"><?= escape_html($absent_title) ?></div>
                <div class="mt-2 text-3xl font-bold text-red-600"><?= (int) $range_attendance_counts['absent'] ?></div>
            </div>
        </div>

        <div class="grid items-start gap-5 lg:grid-cols-12">
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white lg:col-span-7" aria-labelledby="calendar-heading">
                <div class="p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-slate-900" id="calendar-heading">
                            <?= escape_html($month_start->format('F Y')) ?>
                        </h2>
                        <div class="flex overflow-hidden rounded-lg border border-slate-300" aria-label="Calendar month navigation">
                            <a
                                class="px-3 py-1 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                                href="?month=<?= escape_html($previous_month) ?>&amp;range=<?= escape_html($selected_range) ?>"
                                aria-label="Previous month"
                            >&lsaquo;</a>
                            <a
                                class="border-l border-slate-300 px-3 py-1 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                                href="?month=<?= escape_html($following_month) ?>&amp;range=<?= escape_html($selected_range) ?>"
                                aria-label="Next month"
                            >&rsaquo;</a>
                        </div>
                    </div>
                    <div class="grid grid-cols-7 gap-1" role="grid" aria-labelledby="calendar-heading">
                        <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday): ?>
                            <div class="py-1 text-center text-xs font-semibold text-slate-500" role="columnheader"><?= $weekday ?></div>
                        <?php endforeach; ?>
                        <?php for ($day_offset = 0; $day_offset < 42; $day_offset++):
                            $calendar_day = $calendar_start->modify('+' . $day_offset . ' days');
                            $day_key = $calendar_day->format('Y-m-d');
                            $in_month = $calendar_day->format('Y-m') === $month_start->format('Y-m');
                            $day_status = $in_month ? ($calendar_statuses[$day_key] ?? '') : '';
                            $day_class = match (true) {
                                !$in_month => 'flex h-8 items-center justify-center rounded-lg border border-slate-100 bg-slate-50 text-xs text-slate-400',
                                $day_status === 'attended' => 'flex h-8 items-center justify-center rounded-lg bg-emerald-500 text-xs font-semibold text-white',
                                $day_status === 'absent' => 'flex h-8 items-center justify-center rounded-lg bg-red-500 text-xs font-semibold text-white',
                                default => 'flex h-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-700',
                            };
                            $day_status_label = match ($day_status) {
                                'attended' => 'Attended',
                                'absent' => 'Absent',
                                default => 'No record',
                            };
                        ?>
                            <div
                                class="<?= $day_class ?>"
                                role="gridcell"
                                title="<?= escape_html($day_status_label) ?>"
                                aria-label="<?= escape_html($calendar_day->format('F j') . ': ' . $day_status_label) ?>"
                            >
                                <span><?= $calendar_day->format('j') ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="mt-4 flex items-center gap-4 text-xs font-medium text-slate-600 border-t border-slate-100 pt-3" aria-label="Attendance calendar legend">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Attended</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>Absent</span>
                    </div>
                </div>
            </section>

            <!-- Subject Scores Table -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white lg:col-span-5">
                <div class="border-b border-slate-200 bg-slate-50/70 px-4 py-3 text-sm font-semibold text-slate-900">Subject Scores</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-2.5">Subject</th>
                                <th class="px-4 py-2.5 text-right">Score</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (mysqli_num_rows($subject_scores) === 0): ?>
                                <tr>
                                    <td colspan="2" class="px-4 py-6 text-center text-slate-500">No subject scores published yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($subject = mysqli_fetch_assoc($subject_scores)): ?>
                                    <?php
                                    $score_val = rtrim(rtrim((string) $subject['score'], '0'), '.');
                                    $max_val = rtrim(rtrim((string) $subject['max_score'], '0'), '.');
                                    ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-4 py-3 font-medium text-slate-900"><?= escape_html($subject['name']) ?></td>
                                        <td class="px-4 py-3 text-right font-medium text-slate-700"><?= escape_html($score_val) ?> <span class="text-xs text-slate-400">/ <?= escape_html($max_val) ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance Log Side Panel -->
    <aside class="lg:col-span-3">
        <section class="rounded-xl border border-slate-200 bg-white p-5" aria-labelledby="recent-attendance-heading">
            <div class="mb-4 flex items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <h2 class="text-sm font-semibold text-slate-900" id="recent-attendance-heading">Recent Log</h2>
                <a class="text-xs font-medium text-blue-600 hover:text-blue-800 transition-colors" href="attendance.php">View History</a>
            </div>
            <?php if (mysqli_num_rows($recent_attendance) === 0): ?>
                <p class="text-xs text-slate-500 py-2">No attendance records logged yet.</p>
            <?php else: ?>
                <ul class="space-y-2.5">
                    <?php while ($recent_record = mysqli_fetch_assoc($recent_attendance)): ?>
                        <li class="flex items-center justify-between text-xs py-1 border-b border-slate-100 last:border-0">
                            <time class="text-slate-600 font-medium" datetime="<?= escape_html($recent_record['attendance_date']) ?>">
                                <?= escape_html((new DateTimeImmutable($recent_record['attendance_date']))->format('M j, Y')) ?>
                            </time>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold <?= $recent_record['status'] === 'attended' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?>">
                                <?= escape_html(ucfirst($recent_record['status'])) ?>
                            </span>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>
        </section>
    </aside>
</div>
<?php
mysqli_stmt_close($score_statement);
mysqli_stmt_close($recent_attendance_statement);
require __DIR__ . '/../footer.php';
?>
