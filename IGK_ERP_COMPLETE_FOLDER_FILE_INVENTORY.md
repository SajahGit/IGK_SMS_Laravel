# IGK ERP Complete Folder/File Inventory

## Root
- app/ – Laravel application code (Admin, Registrar, Teacher, Accountant, Student, Guardian and Report controllers; Models, Services, Policies, Requests, Middleware, Providers)
- bootstrap/ – bootstrap/app.php, cache
- config/ – framework and package config
- database/ – migrations, seeders, factories
- docs/ – guides and architecture docs
- public/ – entry point and assets
- resources/ – Blade views, CSS/JS
- routes/ – web.php, console.php, role/module route files
- storage/ – logs, framework cache, uploaded files
- tests/ – PHPUnit tests
- vendor/ – Composer dependencies (do not delete)
- node_modules/ – frontend deps (may exclude from ZIP)
- composer.json/lock – dependency manifests
- package.json – frontend manifest
- .env – environment (do not commit secrets)
- artisan – CLI entry

## app/ subfolders
- app/Http/Controllers – resource controllers per module
- app/Http/Requests – form requests
- app/Http/Middleware – auth/role middleware
- app/Models – Eloquent models
- app/Policies – authorization policies
- app/Services – business logic (EnrollmentService, MarksEntryService, GradingService)
- app/Providers – service providers

## database/
- database/migrations – schema
- database/seeders – DatabaseSeeder, GradeScaleSeeder, ProductionSeeder, RolePermissionSeeder, SettingSeeder, SuperAdminSeeder
- database/factories – UserFactory only; SOP model factories are incomplete

## resources/views
- auth/ – login
- components/ – Blade component library
- layouts/ – app layout
- dashboard/ – role dashboards
- students/, academic/, fees/, etc. – module views

## routes/
- web.php – main route manifest
- auth.php – auth routes
- console.php – scheduled commands

## tests/
- Unit/ – service unit tests
- Feature/ – feature tests per module

## docs/
- guides/ – install, environment, deploy, backup-restore, admin
- architecture.md, known-issues.md
- testing/, handover/, team/

## Reports (root)
- IGK_ERP_* markdown reports – audit/handover documentation

Retention: all listed are mandatory/required/development support. No obsolete files identified for deletion in this pass; vendor/ and node_modules/ retained in working app.
