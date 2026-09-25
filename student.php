<?php
require_once __DIR__ . '/auth.php';
require_role('student');
require_once __DIR__ . '/config.php';

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
$page_title = 'My attendance';
require __DIR__ . '/header.php';
?>
<div class="animate-rise-in relative isolate mb-6 overflow-hidden rounded-2xl border border-blue-900 bg-gradient-to-br from-blue-950 via-blue-900 to-indigo-900 px-6 py-8 text-white shadow-xl sm:px-9 sm:py-10">
    <div class="hero-glow hero-glow-blue" aria-hidden="true"></div>
    <div class="hero-glow hero-glow-violet" aria-hidden="true"></div>
    <div class="relative z-10">
        <p class="mb-1 text-lg font-medium tracking-wide text-neutral-400">Hello,</p>
        <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl"><?= escape_html($student['username']) ?></h1>
        <p class="mt-3 max-w-xl text-sm leading-6 text-blue-100 sm:text-base">Here is your attendance and subject progress at a glance.</p>
    </div>
</div>
<div class="mb-6 grid items-stretch gap-6 lg:grid-cols-12">
    <div class="space-y-6 lg:col-span-9">
<div class="flex items-center justify-start">
    <form method="get" class="flex items-center gap-2">
        <?php if (isset($_GET['month'])): ?>
            <input type="hidden" name="month" value="<?= escape_html($_GET['month']) ?>">
        <?php endif; ?>
        <label for="range" class="text-sm font-medium text-slate-700">Timeframe:</label>
        <select
            id="range"
            name="range"
            onchange="this.form.submit()"
            class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
        >
            <option value="weekly"<?= $selected_range === 'weekly' ? ' selected' : '' ?>>Weekly</option>
            <option value="monthly"<?= $selected_range === 'monthly' ? ' selected' : '' ?>>Monthly</option>
            <option value="yearly"<?= $selected_range === 'yearly' ? ' selected' : '' ?>>Yearly</option>
            <option value="entirely"<?= $selected_range === 'entirely' ? ' selected' : '' ?>>Entirely</option>
        </select>
    </form>
</div>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <div class="animate-rise-in animation-delay-100 h-full rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-medium text-slate-600">Attendance points</div>
        <div class="mt-2 text-3xl font-semibold text-blue-900"><?= (int) $range_attendance_counts['attended'] ?></div>
        <p class="mt-1 text-xs text-slate-500"><?= escape_html($points_subtitle) ?></p>
    </div>
    <div class="animate-rise-in animation-delay-200 h-full rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-medium text-slate-600"><?= escape_html($attended_title) ?></div>
        <div class="mt-2 text-3xl font-semibold text-emerald-700"><?= (int) $range_attendance_counts['attended'] ?></div>
        <p class="mt-1 text-xs text-slate-500">Days marked attended</p>
    </div>
    <div class="animate-rise-in animation-delay-300 h-full rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-medium text-slate-600"><?= escape_html($absent_title) ?></div>
        <div class="mt-2 text-3xl font-semibold text-rose-700"><?= (int) $range_attendance_counts['absent'] ?></div>
        <p class="mt-1 text-xs text-slate-500">Days marked absent</p>
    </div>
</div>
<section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="calendar-heading">
    <div class="p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900" id="calendar-heading">
                <?= escape_html($month_start->format('F Y')) ?>
            </h2>
            <div class="flex overflow-hidden rounded-md border border-slate-300 shadow-sm" aria-label="Calendar month navigation">
                <a
                    class="px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-100 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-600"
                    href="?month=<?= escape_html($previous_month) ?>&amp;range=<?= escape_html($selected_range) ?>"
                    aria-label="Previous month"
                >&lsaquo;</a>
                <a
                    class="border-l border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-100 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-600"
                    href="?month=<?= escape_html($following_month) ?>&amp;range=<?= escape_html($selected_range) ?>"
                    aria-label="Next month"
                >&rsaquo;</a>
            </div>
        </div>
        <div class="grid grid-cols-7 gap-1.5" role="grid" aria-labelledby="calendar-heading">
            <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday): ?>
                <div class="py-1 text-center text-xs font-semibold text-slate-500" role="columnheader"><?= $weekday ?></div>
            <?php endforeach; ?>
            <?php for ($day_offset = 0; $day_offset < 42; $day_offset++):
                $calendar_day = $calendar_start->modify('+' . $day_offset . ' days');
                $day_key = $calendar_day->format('Y-m-d');
                $in_month = $calendar_day->format('Y-m') === $month_start->format('Y-m');
                $day_status = $in_month ? ($calendar_statuses[$day_key] ?? '') : '';
                $day_class = match (true) {
                    !$in_month => 'flex h-8 items-center justify-center rounded-md border border-slate-100 bg-slate-50 text-xs text-slate-400',
                    $day_status === 'attended' => 'flex h-8 items-center justify-center rounded-md border border-emerald-300 bg-emerald-100 text-xs text-emerald-900',
                    $day_status === 'absent' => 'flex h-8 items-center justify-center rounded-md border border-rose-300 bg-rose-100 text-xs text-rose-900',
                    default => 'flex h-8 items-center justify-center rounded-md border border-slate-200 bg-white text-xs text-slate-800',
                };
                $day_status_label = match ($day_status) {
                    'attended' => 'Attended',
                    'absent' => 'Absent',
                    default => 'No attendance recorded',
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
        <div class="mt-4 flex gap-4 text-sm text-slate-600" aria-label="Attendance calendar legend">
            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm border border-emerald-300 bg-emerald-100"></span>Attended</span>
            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm border border-rose-300 bg-rose-100"></span>Absent</span>
        </div>
    </div>
</section>
</div>
<aside class="lg:col-span-3">
    <section class="h-full rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="recent-attendance-heading">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-slate-800" id="recent-attendance-heading">Recent attendance</h2>
            <a class="whitespace-nowrap text-xs font-medium text-blue-800 hover:text-blue-950" href="student_attendance.php">View all</a>
        </div>
        <?php if (mysqli_num_rows($recent_attendance) === 0): ?>
            <p class="text-sm text-slate-500">No attendance recorded yet.</p>
        <?php else: ?>
            <ul class="space-y-2">
                <?php while ($recent_record = mysqli_fetch_assoc($recent_attendance)): ?>
                    <li class="flex items-center justify-between gap-2 border-t border-slate-100 pt-2 text-xs">
                        <time class="text-slate-600" datetime="<?= escape_html($recent_record['attendance_date']) ?>">
                            <?= escape_html((new DateTimeImmutable($recent_record['attendance_date']))->format('M j')) ?>
                        </time>
                        <span class="rounded-full px-2 py-0.5 font-semibold <?= $recent_record['status'] === 'attended' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                            <?= escape_html(ucfirst($recent_record['status'])) ?>
                        </span>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>
    </section>
</aside>
</div>
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4 font-semibold text-slate-900">My subjects</div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-5 py-3 font-semibold">Subject</th>
                    <th class="px-5 py-3 font-semibold">Score</th>
                    <th class="px-5 py-3 font-semibold">Maximum</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (mysqli_num_rows($subject_scores) === 0): ?>
                    <tr>
                        <td colspan="3" class="px-5 py-4 text-slate-500">No subjects have been added yet.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($subject = mysqli_fetch_assoc($subject_scores)): ?>
                        <tr class="odd:bg-white even:bg-slate-50">
                            <td class="px-5 py-3"><?= escape_html($subject['name']) ?></td>
                            <td class="px-5 py-3"><?= escape_html(rtrim(rtrim((string) $subject['score'], '0'), '.')) ?></td>
                            <td class="px-5 py-3"><?= escape_html(rtrim(rtrim((string) $subject['max_score'], '0'), '.')) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
mysqli_stmt_close($score_statement);
mysqli_stmt_close($recent_attendance_statement);
require __DIR__ . '/footer.php';
?>
