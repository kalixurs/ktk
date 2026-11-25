# John Paul College – Online Enrollment System

PHP + MySQL (XAMPP). Three kinds of users: **Applicant** (new enrollee), **Student** (approved), **Registrar/Admin**.

Workflow: Applicant submits the form → tracks status → Registrar reviews → **Approve** (student record + account are created) or **Decline** → the approved applicant opens the Student Dashboard.

## 1. What you need
- Windows/Mac/Linux with **XAMPP** (PHP 7.4 or newer, MariaDB/MySQL, Apache). Nothing else to install.
- A web browser.

## 2. Install (step by step)
1. Install XAMPP. Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Copy the folder `Online_enroll_for_sharing` into `C:\xampp\htdocs\`.
3. Open `http://localhost/phpmyadmin`.
4. **Database – choose ONE case:**
   - **A. New computer / fresh install:** click **Import** → choose `online_enroll (2).sql` → **Go**. Then select the `online_enroll` database on the left → **Import** → choose `DATABASE_UPDATE.sql` → **Go**.
   - **B. You already have the `online_enroll` database with your data:** only select it → **Import** → `DATABASE_UPDATE.sql` → **Go**. *Your students, subjects and enrollments are kept.*
   - (If your database came from the very first original project and has a short password column, import `fix_database.sql` first.)
   - Run `DATABASE_UPDATE.sql` **once**. Running it again only shows harmless "duplicate" errors.
5. Open **http://localhost/Online_enroll_for_sharing/**

### Database settings
Only one file: `conn.php` in the project root (host `localhost`, user `root`, empty password, database `online_enroll`). If your MySQL root user has a password, put it in `$DB_PASS`.

## 3. Who uses what
| User | How they get in | Where |
|---|---|---|
| Applicant | Login page → **Enroll Now** → fill the form (they create their own username/password) | `http://localhost/Online_enroll_for_sharing/apply.php`, then `applicant/` dashboard |
| Student | Log in with the account created when the application was approved | `student/` dashboard |
| Registrar/Admin | Log in with an admin account | `Admin/` dashboard |

All three log in from the same page: `http://localhost/Online_enroll_for_sharing/`. The system sends each person to the right dashboard automatically.

### Administrator account (no default password is invented here)
Your original database already contains an admin account (username `admins`). **Change its password right after the first login** (Students page → *My Account*).
To create a new admin: generate a password hash, then insert it in phpMyAdmin (SQL tab):
```
C:\xampp\php\php.exe -r "echo password_hash('TypeYourOwnPassword', PASSWORD_DEFAULT);"
```
```sql
INSERT INTO usertbl (username, password, fullname, user_type)
VALUES ('registrar1', 'PASTE_THE_HASH_HERE', 'Registrar Name', 'admin');
```
The original sample student login (`dennis`) also exists in the old data; delete or change it before real use.

## 4. Registrar guide
- **Applications**: filter by status, search, click **View** to see the complete form. Buttons: *Mark as Under Review*, *Approve*, *Decline* (remarks are shown to the applicant).
- **Approve** creates the student record (student number like `2026-0001`), switches the account from *applicant* to *student*, and links them – all in one database transaction.
- **Students**: *Edit*, *Suspend* (days / weeks / months, ends automatically), *Expel* (keeps all records, blocks login; an expelled student can be reinstated or permanently deleted).
- **Subject Enrollments** (report): subject forms submitted by students, with *Approve*.

## 5. Database changes (`DATABASE_UPDATE.sql`)
| Change | Purpose |
|---|---|
| `usertbl`, `studenttbl`, `student_subject` → InnoDB | foreign keys + transactions (data kept) |
| `studenttbl` + `status`, `suspended_until`, `suspend_reason`, `expelled_at`, `expel_reason` | suspend / expel; suspension ends by date, nothing to run daily |
| `studenttbl` + unique `studnum`, index on `accountID` | no duplicate student numbers |
| **New table `applications`** | one row per application: personal, contact, address, guardian, education, requirements, status (`Pending / Under Review / Approved / Declined`), remarks, timestamps |
| FK `applications.account_id → usertbl.ID` | the applicant's login |
| FK `applications.student_id → studenttbl.ID` | filled when approved |
| FK `applications.reviewed_by → usertbl.ID` | registrar who decided |
| Unique `ref_no`, unique `account_id`; indexes on status/date and name/birthdate | reference numbers, one application per account, fast search & duplicate checks |

`usertbl.user_type` now also uses the value `applicant` (it becomes `student` on approval). The queue position is calculated from real records: waiting applications ordered by submission time.

## 6. Security summary (for your defense)
Prepared statements everywhere (no SQL injection); passwords hashed with `password_hash` (old plain-text passwords are upgraded at login); role is re-checked from the database on every page (`require_role` in `conn.php`); applicants only read their own row; output escaped with `htmlspecialchars`; frontend (HTML5) and backend validation; duplicate prevention (username, email, name + birth date); approval and application saving use transactions; technical errors are hidden from users.

## 7. Troubleshooting
- **"Cannot connect to the database"** → start MySQL in XAMPP and import the SQL files.
- **MySQL won't stay green / "Access denied"** → another MySQL (e.g. MySQL80 service) may be using port 3306. Stop that service in `services.msc`, or change XAMPP's port and set `$DB_HOST = '127.0.0.1:3307'` in `conn.php`.
- **Blank page** → check `C:\xampp\apache\logs\error.log`.
