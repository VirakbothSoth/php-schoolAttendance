<?php
require_once __DIR__ . '/../auth.php';
require_role('staff');
require_once __DIR__ . '/../config.php';

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'demo_login') {
        $student_id = (int) ($_POST['student_id'] ?? 0);
        $demo_statement = mysqli_prepare(
            $link,
            "select id, full_name, username, role from users where id = ? and role = 'student'"
        );
        mysqli_stmt_bind_param($demo_statement, 'i', $student_id);
        mysqli_stmt_execute($demo_statement);
        $target_student = mysqli_fetch_assoc(mysqli_stmt_get_result($demo_statement));
        mysqli_stmt_close($demo_statement);

        if ($target_student) {
            $_SESSION['impersonator_staff_id'] = (int) $_SESSION['user_id'];
            $_SESSION['user_id'] = (int) $target_student['id'];
            $_SESSION['role'] = 'student';
            header('Location: ' . app_base_path() . '/student/overview.php');
            exit;
        }

        $error_message = 'Student account could not be found.';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $full_name = preg_replace('/\s+/', ' ', $full_name);
        
        $username = trim($_POST['username'] ?? '');
        // Silently remove any spaces and control characters from username
        $username = preg_replace('/[\s\x00-\x1F\x7F]+/', '', $username);
        
        $password = $_POST['password'] ?? '';
        
        if ($full_name === '' || $username === '' || strlen($password) < 8) {
            $error_message = 'Enter a name, username, and password of at least 8 characters.';
        } elseif (!preg_match('/^[\p{Latin}\s\-\']+$/u', $full_name)) {
            $error_message = 'Name contains invalid characters.';
        } elseif (preg_match('/[\x00-\x1F\x7F]/', $password)) {
            $error_message = 'Password contains invalid characters.';
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
}
$students = mysqli_query(
    $link,
    "select id, full_name, username from users where role = 'student' order by full_name"
);
$page_title = 'Manage students';
require __DIR__ . '/../header.php';
?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Manage students</h1>
        <p class="text-sm text-slate-500">View registered students or add new student accounts.</p>
    </div>
    <button
        id="openAddStudentModal"
        type="button"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-colors cursor-pointer"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Add student</span>
    </button>
</div>

<?php if (isset($_GET['added'])): ?>
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Student account created.</div>
<?php endif; ?>

<!-- Students Table Card -->
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
    <div class="border-b border-slate-200 bg-slate-50/70 p-4 sm:flex sm:items-center sm:justify-between sm:gap-4">
        <div class="flex items-center gap-2 mb-3 sm:mb-0">
            <h2 class="text-base font-semibold text-slate-800">Students</h2>
            <span id="studentCountBadge" class="rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-semibold text-slate-700"><?= mysqli_num_rows($students) ?></span>
        </div>
        
        <!-- Search and dropdown filters -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div class="relative w-full sm:w-64">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input
                    type="text"
                    id="studentSearchInput"
                    placeholder="Search students..."
                    class="block w-full rounded-md border border-slate-300 bg-white py-1.5 pl-9 pr-3 text-sm placeholder-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
            </div>
            <div class="w-full sm:w-auto">
                <select
                    id="studentSearchType"
                    class="block w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
                    <option value="username" selected>Search by Username</option>
                    <option value="name">Search by Full Name</option>
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm" id="studentsTable">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-5 py-3 font-semibold">Name</th>
                    <th class="px-5 py-3 font-semibold">Username</th>
                    <th class="px-5 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (mysqli_num_rows($students) === 0): ?>
                    <tr id="emptyDbRow">
                        <td colspan="3" class="px-5 py-4 text-slate-500">No students yet.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($student = mysqli_fetch_assoc($students)): ?>
                        <tr class="student-row odd:bg-white even:bg-slate-50" data-name="<?= htmlspecialchars(mb_strtolower($student['full_name'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>" data-username="<?= htmlspecialchars(mb_strtolower($student['username'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-5 py-3 font-medium text-slate-900"><?= escape_html($student['full_name']) ?></td>
                            <td class="px-5 py-3 text-slate-600"><?= escape_html($student['username']) ?></td>
                            <td class="px-5 py-3 text-right">
                                <button
                                    type="button"
                                    class="demo-btn inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100 hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition-colors cursor-pointer"
                                    data-id="<?= (int) $student['id'] ?>"
                                    data-name="<?= escape_html($student['full_name']) ?>"
                                    data-username="<?= escape_html($student['username']) ?>"
                                    title="View student dashboard as this student"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <span>Demo</span>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <tr id="noResultsRow" style="display: none;">
                        <td colspan="3" class="px-5 py-6 text-center text-slate-500">No matching students found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Floating Form Modal for Adding Student -->
<div id="studentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs transition-opacity duration-200 <?= $error_message === '' ? 'hidden' : '' ?>" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div id="modalBackdrop" class="fixed inset-0"></div>
    <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl transition-all">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <h2 id="modalTitle" class="text-lg font-semibold text-slate-900">Add student</h2>
            </div>
            <button type="button" id="closeAddStudentModal" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                <span class="sr-only">Close</span>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <?php if ($error_message !== ''): ?>
            <div class="mt-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <?= escape_html($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="post" id="addStudentForm" class="mt-4 space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="full_name">Full name</label>
                <input
                    class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    id="full_name"
                    name="full_name"
                    maxlength="120"
                    required
                    value="<?= escape_html($_POST['full_name'] ?? '') ?>"
                    placeholder="e.g. John Doe"
                    autocomplete="off"
                >
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="username">Username</label>
                <input
                    class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    id="username"
                    name="username"
                    maxlength="80"
                    required
                    value="<?= escape_html($_POST['username'] ?? '') ?>"
                    placeholder="e.g. John_Doe"
                    autocomplete="off"
                >
                <p class="mt-1 text-xs text-slate-500">Live generated from full name, or type manually without spaces.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="password">Password</label>
                <input
                    class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                    placeholder="At least 8 characters"
                >
            </div>
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button
                    type="button"
                    id="cancelAddStudentModal"
                    class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                >
                    Cancel
                </button>
                <button
                    class="rounded-md bg-blue-800 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-colors cursor-pointer"
                    type="submit"
                >
                    Create student
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Demo Confirmation Modal -->
<div id="demoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs transition-opacity duration-200 hidden" role="dialog" aria-modal="true" aria-labelledby="demoModalTitle">
    <div id="demoModalBackdrop" class="fixed inset-0"></div>
    <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl transition-all">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-800">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 id="demoModalTitle" class="text-base font-semibold text-slate-900">Student Demo Login</h3>
                <p class="mt-1 text-sm text-slate-600">
                    Are you sure you want to log in as <span id="demoStudentName" class="font-semibold text-slate-900"></span> (<span id="demoStudentUsername" class="font-mono text-xs text-slate-600"></span>)?
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    You will be switched to this student's account to view their attendance and progress. An "Exit Demo" button in the top navigation will allow you to return to your staff account at any time.
                </p>
            </div>
        </div>

        <form method="post" id="demoForm" class="mt-6 flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <input type="hidden" name="action" value="demo_login">
            <input type="hidden" name="student_id" id="demoStudentId" value="">
            <button
                type="button"
                id="cancelDemoModal"
                class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="inline-flex items-center gap-1.5 rounded-md bg-blue-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-colors cursor-pointer"
            >
                Yes, log in as student
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Add Student Modal Handling ---
    const modal = document.getElementById('studentModal');
    const openBtn = document.getElementById('openAddStudentModal');
    const closeBtn = document.getElementById('closeAddStudentModal');
    const cancelBtn = document.getElementById('cancelAddStudentModal');
    const backdrop = document.getElementById('modalBackdrop');
    const fullNameInput = document.getElementById('full_name');
    const usernameInput = document.getElementById('username');

    function openModal() {
        modal.classList.remove('hidden');
        fullNameInput.focus();
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    if (openBtn) {
        openBtn.addEventListener('click', function () {
            if (fullNameInput && usernameInput && !fullNameInput.value && !usernameInput.value) {
                usernameTouched = false;
            }
            openModal();
        });
    }
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    // --- Demo Modal Handling ---
    const demoModal = document.getElementById('demoModal');
    const demoBackdrop = document.getElementById('demoModalBackdrop');
    const cancelDemoBtn = document.getElementById('cancelDemoModal');
    const demoStudentIdInput = document.getElementById('demoStudentId');
    const demoStudentNameSpan = document.getElementById('demoStudentName');
    const demoStudentUsernameSpan = document.getElementById('demoStudentUsername');
    const studentsTable = document.getElementById('studentsTable');

    function closeDemoModal() {
        if (demoModal) demoModal.classList.add('hidden');
    }

    if (studentsTable) {
        studentsTable.addEventListener('click', function (e) {
            const btn = e.target.closest('.demo-btn');
            if (!btn) return;

            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const username = btn.dataset.username;

            if (demoStudentIdInput) demoStudentIdInput.value = id;
            if (demoStudentNameSpan) demoStudentNameSpan.textContent = name;
            if (demoStudentUsernameSpan) demoStudentUsernameSpan.textContent = username;

            if (demoModal) demoModal.classList.remove('hidden');
        });
    }

    if (cancelDemoBtn) cancelDemoBtn.addEventListener('click', closeDemoModal);
    if (demoBackdrop) demoBackdrop.addEventListener('click', closeDemoModal);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (modal && !modal.classList.contains('hidden')) {
                closeModal();
            }
            if (demoModal && !demoModal.classList.contains('hidden')) {
                closeDemoModal();
            }
        }
    });

    // --- Live Full Name to Username Auto-update ---
    let usernameTouched = false;

    if (usernameInput && fullNameInput) {
        // If pre-filled on error and username was customized, consider it touched
        if (usernameInput.value && fullNameInput.value && usernameInput.value !== fullNameInput.value.replace(/\s+/g, '_')) {
            usernameTouched = true;
        }

        // Stop auto-updating once username field is touched/interacted with
        ['focus', 'click', 'keydown', 'input'].forEach(function (evt) {
            usernameInput.addEventListener(evt, function () {
                usernameTouched = true;
            });
        });

        // Live update username as full name is typed
        fullNameInput.addEventListener('input', function () {
            if (!usernameTouched) {
                usernameInput.value = fullNameInput.value.replace(/\s+/g, '_');
            }
        });

        // Username live space removal
        usernameInput.addEventListener('input', function () {
            usernameInput.value = usernameInput.value.replace(/\s+/g, '');
        });
    }

    // --- Student Search and Filter ---
    const searchInput = document.getElementById('studentSearchInput');
    const searchType = document.getElementById('studentSearchType');
    const rows = document.querySelectorAll('.student-row');
    const noResultsRow = document.getElementById('noResultsRow');
    const countBadge = document.getElementById('studentCountBadge');

    function filterStudents() {
        const query = searchInput.value.trim().toLowerCase();
        const type = searchType.value; // 'username' or 'name'
        let visibleCount = 0;

        rows.forEach(function (row) {
            const textToMatch = type === 'username' ? (row.dataset.username || '') : (row.dataset.name || '');
            if (!query || textToMatch.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (noResultsRow) {
            noResultsRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }
        if (countBadge) {
            countBadge.textContent = visibleCount;
        }
    }

    if (searchInput && searchType) {
        searchInput.addEventListener('input', filterStudents);
        searchType.addEventListener('change', filterStudents);
    }
});
</script>
<?php
require __DIR__ . '/../footer.php';
?>
