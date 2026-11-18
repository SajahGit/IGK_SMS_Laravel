# Git & Fork Collaboration Guide — Step-by-Step for All Team Members

**Target Remote Branch:** `develop`  
**Git Client:** [Fork](https://fork.dev/) (Windows Git GUI Client)  
**Project:** IGK School ERP (Laravel 12 / PHP 8.3 / MySQL / Vite)  

---

## 1. Overview of the Git Workflow

The team is collaborating on Sir's shared repository. The base branch is `develop`. Each member (Alass, Sumaiya, Rajiya, Hasfa, Shilfa) has their own standalone ZIP package (`*_PACKAGE.zip`).

Here is the golden rule:
1. **Pull first** to ensure you have the latest code from `origin/develop`.
2. **Extract your ZIP file** directly into the project root.
3. **Inspect changes** in Fork (see exactly what files were added or modified).
4. **Stage and commit** with standard conventional commit prefixes (`feat(admin):`, `feat(teacher):`, etc.).
5. **Push** to `develop`.

---

## 2. Setting Up Fork Application on Windows

### Step 1: Clone Sir's Repository in Fork
1. Open the **Fork** application on your Windows machine.
2. In the top menu, click **File > Clone** (or press `Ctrl + N`).
3. Enter the Repository URL provided by Sir (e.g. `https://github.com/sir-account/IGK_SMS_Laravel.git`).
4. Choose your local destination folder (e.g. `C:\Projects\IGK_SMS_Laravel` or `D:\IGK_SMS_Laravel`).
5. Click **Clone** and wait for Fork to finish downloading the files.

### Step 2: Switch to the `develop` Branch
1. In Fork's left sidebar, look under the **Branches** or **Remotes (origin)** section.
2. Find `develop` (or `origin/develop`).
3. Double-click on `origin/develop`.
4. Fork will pop up a window: **Checkout Branch**. Ensure the branch name is `develop` and click **Checkout**.
5. Verify in Fork's top bar that the active branch is now `develop` (indicated by a bold branch icon).

---

## 3. How Each Person Pastes Their ZIP Package

Each team member has a dedicated standalone ZIP package located in the central `ALL DOCS` folder (`G:\ALL DOCS\packages\`):
* Alass: `ALASS_ADMIN_PACKAGE.zip`
* Sumaiya: `SUMAIYA_TEACHER_PACKAGE.zip`
* Rajiya: `RAJIYA_STUDENT_PACKAGE.zip`
* Hasfa: `HASFA_REPORTS_PACKAGE.zip`
* Shilfa: `SHILFA_FINANCE_PACKAGE.zip`
* Foundation: `CORE_SHARED_SYSTEM_PACKAGE.zip`

### Step 3: Extract Package Directly into Your Repository Root
1. Copy your assigned `.zip` file from `G:\ALL DOCS\packages\` (or `G:\ALL DOCS\`).
2. Paste the `.zip` file directly into the **root folder** of your cloned repository (where `artisan`, `composer.json`, and `app/` are located).
3. Right-click the `.zip` file and select **Extract All...** (or extract using 7-Zip / WinRAR) directly into the current directory.
   > **Note:** Choose to overwrite/merge folders if prompted. The internal folder structure (`app/...`, `resources/views/...`, `routes/...`, `tests/...`) matches the repository layout exactly.
4. Delete the `.zip` file from your local folder so it doesn't get committed (only system code stays inside the repository).

---

## 4. Staging, Committing & Pushing Using Fork Application

### Step 4: Review Your Uncommitted Changes in Fork
1. Return to the **Fork** application window.
2. In the top-left section, click on **Uncommitted Changes** (or press `Ctrl + D`).
3. You will see two panes:
   * **Unstaged Files** (all the new or updated files from your package).
   * **Diff Viewer** on the right (showing the exact code changes).
4. Review the list of files to confirm they match your assigned module scope.

### Step 5: Stage Your Files
* To stage all files at once: Click the **Stage All** button (or press `Ctrl + Shift + S`).
* The files will move to the **Staged Files** section.

### Step 6: Write a Professional Commit Message
In the bottom-right commit box, enter a standardized commit message:

* **For Alass:**
  ```text
  feat(admin): implement multi-campus branch switching, user search/filter, and HR payroll
  ```
* **For Sumaiya:**
  ```text
  feat(teacher): integrate marks entry boundary validation, timetable clash detector, and grading engine
  ```
* **For Rajiya:**
  ```text
  feat(student): implement student admissions SIS, guardian portal multi-child switching, and transcripts
  ```
* **For Hasfa:**
  ```text
  feat(reports): add 11 master reports suite, memory-safe CSV streaming, and library circulation
  ```
* **For Shilfa:**
  ```text
  feat(finance): add cohort invoicing, counter payments, double-entry general ledger, and procurement
  ```

Click the **Commit** button (or press `Ctrl + Enter`).

### Step 7: Pull Before Pushing (Crucial to Avoid Conflicts!)
Before pushing, always fetch and rebase/merge any changes pushed by your teammates:
1. In Fork's top toolbar, click the **Pull** button (downward arrow).
2. Select **develop** as the branch.
3. Choose **Rebase** (or **Merge**).
4. Click **Pull**.
5. If there are no conflicts, Fork will fast-forward smoothly.

### Step 8: Push to Sir's `develop` Branch
1. In Fork's top toolbar, click the **Push** button (upward arrow).
2. Ensure the dialog shows:
   * **Branch:** `develop`
   * **Remote:** `origin`
3. Click **Push**.
4. Fork will push your commit to `origin/develop`. A green notification banner will confirm success!

---

## 5. Handling Merge Conflicts in Fork (If Any)

Because each person owns their own controllers, views, models, and routes, conflicts are rare. If a conflict occurs on a shared file (e.g. `LayoutApp.blade.php`):
1. Fork will show a red **Conflicted** state on that file.
2. Right-click the conflicted file and select **Resolve Conflicts > Open in Merge Tool** (or use Fork's built-in 3-way merge editor).
3. Choose your teammate's incoming changes and keep your additions.
4. Save the file, click **Resolve**, and click **Continue Rebase / Commit Merge**.
5. Push your resolved commit.

---

## 6. How Every Team Member Runs the Project Locally

After pulling or pasting your files, follow these exact steps to run and verify the system on your computer:

### Step 1: Open Terminal in the Project Root
In Fork, right-click anywhere on the repository name in the sidebar and click **Open in Terminal** (PowerShell or Command Prompt).

### Step 2: Environment Configuration (`.env`)
If you do not have a `.env` file yet:
```powershell
Copy-Item .env.example .env
php artisan key:generate
```
Open `.env` and verify your MySQL database credentials:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_management_system_db
DB_USERNAME=root
DB_PASSWORD=
```

### Step 3: Install Dependencies
```powershell
# Install PHP dependencies
composer install

# Install Frontend dependencies
npm install
```

### Step 4: Run Database Migrations & Seeders
```powershell
# Run all 61 database schema migrations
php artisan migrate

# Seed roles, permissions, branches, and sample accounts
php artisan db:seed
```

### Step 5: Build Frontend Assets
```powershell
npm run build
```

### Step 6: Start the Development Server
```powershell
php artisan serve
```
Open your browser and navigate to:  
👉 **http://127.0.0.1:8000**

---

## 7. Demo Login Credentials for Testing

Use these accounts to sign in and test your respective areas:

| Role | Email Address | Password | Landing Portal |
| :--- | :--- | :--- | :--- |
| **Super Admin (Alass)** | `superadmin@school.test` | `Password123!` (2FA Code: `434649`) | `/admin/dashboard` |
| **Institution Admin (Alass)** | `admin@school.test` | `Password123!` (2FA Code: `434649`) | `/admin/dashboard` |
| **Teacher (Sumaiya)** | `teacher@school.test` | `Password123!` | `/teacher/dashboard` |
| **Registrar (Rajiya)** | `registrar@school.test` | `Password123!` | `/registrar/dashboard` |
| **Student (Rajiya)** | `student@school.test` | `Password123!` | `/student/dashboard` |
| **Guardian / Parent (Rajiya)**| `guardian@school.test` | `Password123!` | `/guardian/dashboard` |
| **Librarian (Hasfa)** | `librarian@school.test` | `Password123!` | `/library/dashboard` |
| **Accountant (Shilfa)** | `finance@school.test` | `Password123!` | `/accountant/dashboard` |

---

## 8. Summary Checklist Before Submitting to Sir

- [ ] All package files extracted without missing files.
- [ ] Fork client shows clean branch `develop` without stray untracked files.
- [ ] Database migrated with 0 errors (`php artisan migrate:status`).
- [ ] Feature test suites pass 100% (`php vendor/bin/phpunit`).
- [ ] Vite frontend build passes with 0 errors (`npm run build`).
- [ ] Changes pushed to `origin/develop`.
