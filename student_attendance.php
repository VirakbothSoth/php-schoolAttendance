<?php
require_once __DIR__ . '/auth.php';
require_role('student');
require_once __DIR__ . '/config.php';

$month_input = $_GET['month'] ?? date('Y-m');
$valid_month = is_string($month_input) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month_input);
$month_start = $valid_month
    ? DateTimeImmutable::createFromFormat('!Y-m-d', $month_input . '-01')
    : false;
if (!$month_start) {
    $month_start = new DateTimeImmutable('first day of this month');
}
$next_month = $month_start->modify('first day of next month');
$month_first_day = $month_start->format('Y-m-d');
$next_month_first_day = $next_month->format('Y-m-d');

$statement = mysqli_prepare(
    $link,
    'select attendance_date, status from attendance '
        . 'where student_id = ? and attendance_date >= ? and attendance_date < ? '
        . 'order by attendance_date desc'
);
mysqli_stmt_bind_param($statement, 'iss', $_SESSION['user_id'], $month_first_day, $next_month_first_day);
mysqli_stmt_execute($statement);
$record_result = mysqli_stmt_get_result($statement);
$weeks = [];
while ($record = mysqli_fetch_assoc($record_result)) {
    $date = new DateTimeImmutable($record['attendance_date']);
    $week_start = $date->modify('monday this week');
    $week_end = $week_start->modify('+6 days');
    $week_key = $week_start->format('Y-m-d');
    $weeks[$week_key] ??= [
        'label' => $week_start->format('M j') . ' – ' . $week_end->format('M j'),
        'records' => [],
    ];
    $record['display_date'] = $date->format('D, M j');
    $weeks[$week_key]['records'][] = $record;
}

$page_title = 'Attendance history';
require __DIR__ . '/header.php';
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="mb-1 text-2xl font-semibold tracking-tight text-slate-900">Attendance history</h1>
        <p class="text-sm text-slate-600">Review your attendance by month.</p>
    </div>
    <a class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-600" href="student.php">Back to overview</a>
</div>
<section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="attendance-heading">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900" id="attendance-heading"><?= escape_html($month_start->format('Y')) ?></h2>
            <p class="text-sm text-slate-600"><?= escape_html($month_start->format('F')) ?> attendance</p>
        </div>
        <form method="get" class="flex flex-wrap items-center gap-2">
            <label for="month" class="text-sm font-medium text-slate-700">Month</label>
            <input
                class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                type="month"
                id="month"
                name="month"
                value="<?= escape_html($month_start->format('Y-m')) ?>"
                required
            >
            <button class="rounded-md bg-blue-800 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2" type="submit">View</button>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-5 py-3 font-semibold" scope="col">Date</th>
                    <th class="px-5 py-3 font-semibold" scope="col">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if ($weeks === []): ?>
                    <tr>
                        <td colspan="2" class="px-5 py-4 text-slate-500">
                            No attendance or absence has been recorded for <?= escape_html($month_start->format('F Y')) ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($weeks as $week): ?>
                        <tr class="bg-slate-100">
                            <th colspan="2" class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600" scope="rowgroup">Week of <?= escape_html($week['label']) ?></th>
                        </tr>
                        <?php foreach ($week['records'] as $record): ?>
                            <tr class="odd:bg-white even:bg-slate-50">
                                <td class="px-5 py-3 text-slate-700"><time datetime="<?= escape_html($record['attendance_date']) ?>"><?= escape_html($record['display_date']) ?></time></td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $record['status'] === 'attended' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' ?>">
                                        <?= escape_html(ucfirst($record['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
mysqli_stmt_close($statement);
require __DIR__ . '/footer.php';
?>
