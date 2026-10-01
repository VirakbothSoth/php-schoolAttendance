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
$page_title = 'Manage Students';
require __DIR__ . '/../header.php';
?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Manage Students</h1>
        <p class="text-sm text-slate-500">View registered students or create new student accounts.</p>
    </div>
    <button
        id="openAddStudentModal"
        type="button"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-colors cursor-pointer"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Add Student</span>
    </button>
</div>

<?php if (isset($_GET['added'])): ?>
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">Student account created successfully.</div>
<?php endif; ?>

<!-- Students Table Card -->
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
    <div class="border-b border-slate-200 bg-slate-50/80 px-5 py-4 sm:flex sm:items-center sm:justify-between sm:gap-4">
        <div class="flex items-center gap-2.5 mb-3 sm:mb-0">
            <h2 class="text-base font-semibold text-slate-900">Student Directory</h2>
            <span id="studentCountBadge" class="rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-semibold text-slate-700"><?= mysqli_num_rows($students) ?></span>
        </div>
        
        <!-- Search and dropdown filters -->
        <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
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
                    class="block w-full rounded-lg border border-slate-300 bg-white py-1.5 pl-9 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                >
            </div>
            <div class="w-full sm:w-auto">
                <select
                    id="studentSearchType"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                >
                    <option value="username" selected>Search by Username</option>
                    <option value="name">Search by Full Name</option>
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm" id="studentsTable">
            <thead class="bg-slate-50 text-md text-slate-500 border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3">Full Name</th>
                    <th class="px-5 py-3">Username</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (mysqli_num_rows($students) === 0): ?>
                    <tr id="emptyDbRow">
                        <td colspan="3" class="px-5 py-6 text-center text-slate-500">No student accounts registered yet.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($student = mysqli_fetch_assoc($students)): ?>
                        <tr class="student-row hover:bg-slate-50/80 transition-colors" data-name="<?= htmlspecialchars(mb_strtolower($student['full_name'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>" data-username="<?= htmlspecialchars(mb_strtolower($student['username'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-5 py-3.5 font-medium text-slate-900"><?= escape_html($student['full_name']) ?></td>
                            <td class="px-5 py-3.5 text-slate-600 font-mono"><?= escape_html($student['username']) ?></td>
                            <td class="px-5 py-3.5 text-right">
                                <button
                                    type="button"
                                    class="demo-btn inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600 transition-colors cursor-pointer"
                                    data-id="<?= (int) $student['id'] ?>"
                                    data-name="<?= escape_html($student['full_name']) ?>"
                                    data-username="<?= escape_html($student['username']) ?>"
                                    title="View student dashboard as this student"
                                >
                                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <span>Demo View</span>
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

<!-- Modal for Adding Student -->
<div id="studentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 transition-opacity duration-200 <?= $error_message === '' ? 'hidden' : '' ?>" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div id="modalBackdrop" class="fixed inset-0"></div>
    <div class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-xl transition-all">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <h2 id="modalTitle" class="text-base font-semibold text-slate-900">Add New Student</h2>
            <button type="button" id="closeAddStudentModal" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                <span class="sr-only">Close</span>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <?php if ($error_message !== ''): ?>
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <?= escape_html($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="post" id="addStudentForm" class="mt-4 space-y-4" autocomplete="off">
            <!-- Hidden dummy inputs to prevent browser & password manager autofill -->
            <input type="text" name="fake_username_remember" style="display:none" tabindex="-1" aria-hidden="true" autocomplete="off">
            <input type="password" name="fake_password_remember" style="display:none" tabindex="-1" aria-hidden="true" autocomplete="off">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="full_name">Full Name</label>
                <input
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                    id="full_name"
                    name="full_name"
                    maxlength="120"
                    required
                    value="<?= escape_html($_POST['full_name'] ?? '') ?>"
                    placeholder="e.g. Jane Smith"
                    autocomplete="off"
                >
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="username">Username</label>
                <input
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                    id="username"
                    name="username"
                    maxlength="80"
                    required
                    value="<?= escape_html($_POST['username'] ?? '') ?>"
                    placeholder="e.g. Jane_Smith"
                    autocomplete="new-password"
                >
                <p class="mt-1 text-xs text-slate-500">Automatically generated from full name, or enter manually.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="password">Password</label>
                <input
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20"
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                    placeholder="At least 8 characters"
                    autocomplete="new-password"
                >
            </div>
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button
                    type="button"
                    id="cancelAddStudentModal"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                >
                    Cancel
                </button>
                <button
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-colors cursor-pointer"
                    type="submit"
                >
                    Create Student
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Demo Confirmation Modal -->
<div id="demoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 transition-opacity duration-200 hidden" role="dialog" aria-modal="true" aria-labelledby="demoModalTitle">
    <div id="demoModalBackdrop" class="fixed inset-0"></div>
    <div class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-xl transition-all">
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 id="demoModalTitle" class="text-base font-semibold text-slate-900">Student Demo View</h3>
                <p class="mt-1 text-sm text-slate-600">
                    Switch to viewing as <span id="demoStudentName" class="font-semibold text-slate-900"></span> (<span id="demoStudentUsername" class="font-mono text-xs text-slate-600"></span>)?
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    You can exit demo mode at any time using the header navigation bar.
                </p>
            </div>
        </div>

        <form method="post" id="demoForm" class="mt-5 flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <input type="hidden" name="action" value="demo_login">
            <input type="hidden" name="student_id" id="demoStudentId" value="">
            <button
                type="button"
                id="cancelDemoModal"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-colors cursor-pointer"
            >
                Switch View
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
        if (usernameInput.value && fullNameInput.value && usernameInput.value !== fullNameInput.value.replace(/\s+/g, '_')) {
            usernameTouched = true;
        }

        ['focus', 'click', 'keydown', 'input'].forEach(function (evt) {
            usernameInput.addEventListener(evt, function () {
                usernameTouched = true;
            });
        });

        fullNameInput.addEventListener('input', function () {
            if (!usernameTouched) {
                usernameInput.value = fullNameInput.value.replace(/\s+/g, '_');
            }
        });

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
        const type = searchType.value;
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
