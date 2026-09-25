\# AGENTS.md



\## Project overview

A school management website (PHP + MySQL) with two roles: \*\*students\*\* and \*\*staff\*\* (teachers).

Core feature is attendance tracking. Auth via PHP sessions. Styling should look clean and

professional, like a well-made school/academic project — not flashy, not a generic template dump.



\## Roles \& pages

\- \*\*Student (logged in):\*\*

&#x20; - Sees only their own attendance record.

&#x20; - Has a points system: each time they're marked "attended" for a date, they earn a point.

&#x20;   Show their total points/attendance count somewhere visible on their page.

&#x20; - No access to other students' data or any management pages.

\- \*\*Staff/teacher (logged in):\*\*

&#x20; - Navbar with at least two pages:

&#x20;   1. \*\*Manage students\*\* — view/manage the student list (e.g. add/edit/remove students,

&#x20;      view individual records).

&#x20;   2. \*\*Attendance\*\* — select a date, see a list of all students, and mark each one as

&#x20;      "attended" or "absent" for that date. Should support updating an existing day's

&#x20;      attendance (not just one-time entry) in case of mistakes.

&#x20; - No student-only views mixed in; keep staff and student page logic clearly separated.

\- Role is checked server-side on every protected page (see Authentication \& security below) —

&#x20; never trust a role flag from the client or from hidden form fields alone.



\## Setup

\- Database connection settings live in `config.php` — always read this file first before

&#x20; writing any DB code. Never hardcode credentials elsewhere.

\- Local server: run via XAMPP/WAMP/PHP built-in server (`php -S localhost:8000`), whichever

&#x20; the project already uses — check for existing config before assuming.

\- Database: MySQL. Table schemas are designed by Codex as the project develops — check

&#x20; existing `.sql` files or migration scripts before creating new tables, and keep schema

&#x20; changes documented (a `schema.sql` or `/db/migrations` folder is preferred over ad hoc edits).



\## Authentication \& security

\- Passwords must always be hashed with `password\_hash()` (bcrypt/argon2) and verified with

&#x20; `password\_verify()`. Never store or log plaintext passwords.

\- Sessions are used for auth state (`session\_start()` at the top of protected pages). Store

&#x20; only minimal data in `$\_SESSION` (e.g. user id, role) — not full user records.

\- Regenerate the session ID on login (`session\_regenerate\_id(true)`) to prevent session

&#x20; fixation.

\- All SQL queries must use prepared statements (PDO or mysqli with bound parameters) — no

&#x20; raw string concatenation into queries, ever.

\- Validate and sanitize all user input server-side, even if there's client-side validation too.

\- Role-based access: check the logged-in user's role (student/teacher/admin) on every

&#x20; protected page, not just at login.



\## Personal coding style

\- Some pages should share HTML and PHP code rather than duplicating it — use includes/partials

&#x20; (e.g. shared header, footer, navbar, or common form/table markup) wherever multiple pages

&#x20; need the same structure, instead of copy-pasting the same block across files.

\- SQL queries are written in \*\*lowercase\*\* (keywords included, e.g. `select \* from users where

&#x20; id = ?`) to match personal writing style — don't auto-capitalize `SELECT`, `FROM`, `WHERE`,

&#x20; etc.



\## Code style

\- No "vibe-coded" elements: every piece of code should be intentional and understood, not

&#x20; copy-pasted boilerplate or AI-guessed filler left unreviewed. Avoid unused variables,

&#x20; dead code, placeholder comments like `// TODO: fix later` left unresolved, or inconsistent

&#x20; patterns copied from different sources. If a snippet's purpose can't be clearly explained,

&#x20; rewrite it properly instead of leaving it in.

\- PHP: consistent indentation (4 spaces), meaningful variable names, no mixing of tabs/spaces.

\- Separate concerns where practical: DB logic, page logic, and HTML output shouldn't all be

&#x20; tangled in one giant file if it can be avoided.

\- Reuse `config.php` and any shared includes (e.g. `db.php`, `auth.php`, `header.php`,

&#x20; `footer.php`) instead of duplicating connection or layout code across pages.

\- Comment non-obvious logic, especially around auth checks and query building.



\## Styling / frontend

\- \*\*Bootstrap\*\* is the CSS framework used throughout (via CDN or local files — check existing

&#x20; pages for which). Use Bootstrap components (navbar, tables, forms, buttons, badges/cards for

&#x20; showing points) instead of writing custom CSS from scratch where a Bootstrap equivalent exists.

\- Look and feel: clean, professional, "well-made school project" aesthetic — a simple, consistent

&#x20; Bootstrap theme (default or lightly customized colors), readable fonts, clear navigation.

&#x20; Avoid overly playful or overly flashy styling.

\- Use Bootstrap's navbar component for the staff navigation (Manage Students / Attendance) and

&#x20; keep it consistent across staff pages; students don't need the same nav since they only have

&#x20; one page.

\- Any custom CSS beyond Bootstrap should live in one shared file (e.g. `style.css`), not inline,

&#x20; and should be minimal — small tweaks on top of Bootstrap rather than overrides everywhere.

\- Use Bootstrap's table styling for the attendance list (student names + attended/absent

&#x20; controls) and for any student management list.

\- Reuse shared header/footer/nav includes across pages for a consistent look.



\## Database

\- `config.php` is the single source of truth for DB connection info (host, db name, user,

&#x20; password). Read from it — never duplicate credentials in other files.

\- Table design is handled by Codex; when adding a new table or column, check for an existing

&#x20; schema file first and update it, rather than creating conflicting or duplicate structures.

\- Use foreign keys where relationships exist (e.g. a `users` table referenced by an

&#x20; `attendance` table) rather than loosely linked IDs with no constraints.

\- Expected core tables (adjust/extend as Codex designs the schema):

&#x20; - `users` — holds all accounts (students and staff), with a `role` column (`student` /

&#x20;   `staff`), hashed password, and identifying info.

&#x20; - `attendance` — records attendance per student per date (e.g. `student\_id`, `date`,

&#x20;   `status` = attended/absent). One row per student per date; a unique constraint on

&#x20;   (`student\_id`, `date`) is recommended to prevent duplicate entries when a teacher

&#x20;   re-marks a day.

\- A student's "points" are derived from counting their `attendance` rows marked "attended" —

&#x20; prefer computing this with a query (`COUNT(\*) WHERE status = 'attended'`) rather than storing

&#x20; a separately maintained points total, unless performance later requires caching it.



\## Things to avoid

\- Don't hardcode DB credentials outside `config.php`.

\- Don't store plaintext or reversibly-encrypted passwords — hashed only.

\- Don't build SQL queries via string concatenation with user input.

\- Don't put session-sensitive or secret data directly into cookies outside of the session

&#x20; mechanism.

\- Don't introduce a new CSS framework or major dependency without flagging it — keep the

&#x20; stack simple and consistent with a typical school project scope.



\## Testing / verification

\- After changes to auth or DB logic, manually verify: login, logout, session persistence

&#x20; across pages, and access control (that a student can't reach admin-only pages, etc.).

\- Check that failed logins and invalid input show reasonable error messages without leaking

&#x20; system/database details.

