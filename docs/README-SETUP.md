# IGK School ERP — Windows team setup

இந்த guide புதிய teammate ஒருவர் project-ஐப் பெற்று, தனக்கான local MySQL database-ல் ஒரே synthetic demo dataset-ஐ உருவாக்குவதற்கான படிகள். **ஒவ்வொருவரும் தங்களுக்கான `.env` மற்றும் local MySQL database வைத்திருக்க வேண்டும்.** `.env`, DB password, real student data அல்லது database dump-ஐ Git/ZIP/chat-ல் பகிர வேண்டாம்.

## 1. தேவைகள்

- PHP 8.2+; `bcmath`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`
- Composer 2
- Node.js 20.19+ மற்றும் npm
- MySQL 8 மற்றும் phpMyAdmin அல்லது MySQL client
- Git (fork/branch collaboration-க்கு)

Automated tests-க்கு `phpunit.xml` in-memory SQLite பயன்படுத்தும்; local app/demo DB MySQL ஆக இருக்கும்.

## 2. Project பெறுதல்

ஒவ்வொரு developer-மும் GitHub-ல் தங்களுடைய fork உருவாக்கி தங்களது username-ஐ பயன்படுத்தவும்:

```powershell
git clone https://github.com/<your-user>/IGK_SMS_Laravel.git
cd IGK_SMS_Laravel
git remote add upstream https://github.com/SajahGit/IGK_SMS_Laravel.git
git remote -v
```

Team repository-க்கு write access உள்ள collaborator fork இல்லாமல் clone செய்யலாம்:

```powershell
git clone https://github.com/SajahGit/IGK_SMS_Laravel.git
cd IGK_SMS_Laravel
```

Feature-க்கு தனி branch:

```powershell
git checkout -b feature/<name>-<module>
```

மாற்றங்களை commit செய்து branch-ஐ push செய்து team repository-யின் `master` branch-க்கு PR திறக்கவும். `master`-க்கு நேரடியாக push செய்ய வேண்டாம்:

```powershell
git add <changed-files>
git commit -m "Describe the module change"
git push -u origin feature/<name>-<module>
```

பிறரின் merged changes பெற:

```powershell
git fetch upstream
git checkout master
git pull upstream master
git checkout feature/<name>-<module>
git merge master
```

ZIP snapshot-ல் Git history/remotes இருக்காது; தொடர்ந்து pull/push செய்ய Git fork/clone பயன்படுத்த வேண்டும்.

## 3. Dependencies மற்றும் தனிப்பட்ட `.env`

Project root-ல்:

```powershell
.\setup-team.bat
```

இந்த script Composer/npm dependencies நிறுவி frontend build செய்கிறது. `.env` இல்லாவிட்டால் `.env.example` copy செய்து புதிய தனிப்பட்ட `APP_KEY` உருவாக்கும்; ஏற்கனவே உள்ள `.env`-ஐ overwrite செய்யாது.

Script பயன்படுத்தாமல் `.env` உருவாக்க:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

`.env.example` MySQL-ஐவும் கீழே உள்ள DB பெயரையும் அமைக்கிறது:

```dotenv
APP_ENV=local
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_management_system_testing_db
DB_USERNAME=<your-local-mysql-user>
DB_PASSWORD=<your-local-mysql-password>
```

`DB_USERNAME`/`DB_PASSWORD`-ஐ **உங்கள் local MySQL login-க்கு** மாற்றி, `.env`-ஐ team-க்கு அனுப்ப வேண்டாம். Mail-க்கு `MAIL_FROM_ADDRESS="hello@example.com"` போன்ற plain email format பயன்படுத்தவும்; Markdown `mailto:` link அல்ல.

### Team DB பெயர் மற்றும் isolation

ஒவ்வொரு teammate-மும் தனித்தனி laptop-ல்/local MySQL server-ல் `school_management_system_testing_db` என்ற ஒரே பெயரைப் பயன்படுத்தலாம். MySQL instances தனித்தனியாக இருப்பதால் ஒவ்வொருவருக்கும் தனித்த database; அதே seeders ஓட்டியதால் ஒரே sample starting data.

**ஒரே MySQL server/database-ஐ எல்லோரும் பயன்படுத்த வேண்டாம்.** ஒரே DB-க்கு அனைவரும் connect ஆனால் அது ஒரே live dataset; ஒருவரின் மாற்றம் மற்றவர்களுக்கு உடனே தெரியும், conflict/accidental deletion அபாயம் உண்டு. Shared DB வேண்டுமானால், தனி approved shared dev server, backups, user permissions மற்றும் team coordination கொண்டு அமைக்கவும். `.env` அல்லது DB credentials-ஐ பகிர்வது அதற்கான சரியான வழி அல்ல.

### phpMyAdmin-ல் DB உருவாக்குதல்

உங்கள் local MySQL (`127.0.0.1:3306`) இயங்குகிறதா உறுதிசெய்து phpMyAdmin → **SQL** tab-ல்:

```sql
CREATE DATABASE school_management_system_testing_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

இது ஏற்கனவே இருக்கிறதெனில் மீண்டும் create செய்ய வேண்டாம். புதிய schema-வை create செய்வது வேறு DB-யிலிருந்து பழைய data-வை copy/move செய்யாது.

## 4. Migration மற்றும் ஒரே demo dataset

`.env`-ஐச் சேமித்த பின் **database-ஐ தொடும் எந்த command-க்கும் முன்** target-ஐ உறுதி செய்யவும்:

```powershell
php artisan config:clear
php artisan config:show database.default
php artisan config:show database.connections.mysql.database
```

Connection `mysql` ஆகவும் DB name `school_management_system_testing_db` ஆகவும் இல்லாவிட்டால் கீழே தொடர வேண்டாம்.

புதிய local DB-க்கு:

```powershell
php artisan migrate --seed
php artisan storage:link
```

`DatabaseSeeder` local/testing-ல் முழு `FullSampleDataSeeder` chain-ஐ ஓட்டி synthetic students, staff, academic/finance மற்றும் module demo records உருவாக்குகிறது. ஒவ்வொரு teammate-மும் தங்களது local DB-ல் இதே migrations/seeders ஓட்டினால் ஒரே sample starting dataset கிடைக்கும். Teammate A-வின் பிறகு உள்ள user-created changes, Teammate B-க்கு தானாக வராது; committed migration/seeder அல்லது review செய்யப்பட்ட sanitized import தேவை.

Git update-ல் புதிய migrations வந்தால், சாதாரணமாக:

```powershell
php artisan migrate
```

Clean sample dataset-க்கு மட்டும், DB பெயரை மீண்டும் உறுதி செய்த பிறகு:

```powershell
php artisan migrate:fresh --seed
```

**கவனம்:** `migrate:fresh` அந்த `.env`-ல் தேர்ந்த DB-இன் எல்லா tables மற்றும் records-ஐ அழிக்கும். `school_management_new`, shared/team DB, production அல்லது முக்கிய data உள்ள DB-ல் இயக்க வேண்டாம். Seeder-ஐ production-ல் இயக்க வேண்டாம்.

## 5. App மற்றும் demo logins

```powershell
php artisan serve
```

Browser-ல் `http://127.0.0.1:8000` திறக்கவும். Role login email-கள், local demo password, QA வழிமுறைகள் [sample dataset.md](./sample%20dataset.md)-ல் உள்ளன. Demo credentials local/testing-க்கு மட்டுமே; production-க்கு வேண்டாம். Super Admin, Institution Admin-க்கு local 2FA setup இருக்கலாம்.

## 6. Automated verification

```powershell
php artisan test
php vendor\bin\pint --test
npm run build
```

`php artisan test`-ல் `phpunit.xml` தனி SQLite `:memory:` database-ஐப் பயன்படுத்தும்; அது local MySQL demo DB அல்ல.

## 7. தற்போது உள்ள DB குறித்த கண்டறிதல்

2026-11-14 அன்று இந்த workspace-ஐ read-only ஆகச் சரிபார்த்தபோது, உண்மையான `.env` posted SQLite example-ஐவிட வேறுபட்டிருந்தது:

- Active Laravel connection: `mysql`
- Host/port: `127.0.0.1:3306`
- Active schema: `school_management_new`
- `users` table-ல் 10,017 rows; 64 migrations applied; `2026_11_13_140100_expand_academic_identifiers` மற்றும் `2026_11_13_154100_expand_library_category_name` pending
- Read-only count-ல் documented demo identifiers இருந்தன: Super Admin 1, `demo.student.*` users 239, பட்டியலிட்ட standard role logins-ல் 6
- `school_management_system_testing_db` அந்த server-ன் schema பட்டியலில் இல்லை

இந்த snapshot பின்னர் மாறியிருக்கலாம். `.env`-ல் காட்டும் DB பெயரே app connect செய்யும் schema; phpMyAdmin-ல் அதே local server → Databases பட்டியலில் இருக்கும். `database\database.sqlite` இந்த workspace-ல் இல்லை. `php artisan db:show --counts` இந்த MySQL setup-ல் `performance_schema.session_status` missing என்ற பிழை தந்ததால், அது DB இல்லையென்பதற்கான சான்று அல்ல; config-ஐ மேலுள்ள `config:show` மூலம், schema/migrations-ஐ phpMyAdmin மற்றும் `migrate:status` மூலம் சரிபார்க்கவும்.

முன்பு உருவாக்கிய Markdown documentation மற்றும் `.env.example` மட்டும் எந்த database records-யும் சேர்க்கவில்லை. தற்போதைய `school_management_new`-ல் ஏற்கனவே சில demo identifiers/records இருந்தன; அவை இந்த docs மாற்றங்களால் சேர்க்கப்பட்டவை அல்ல. புதிய target DB-க்கு `migrate --seed` ஓட்டினால்தான் அங்கே sample records உருவாகும்; அது `school_management_new`-இலுள்ள records-ஐ தானாக மாற்றவோ copy செய்யவோ செய்யாது.

## 8. phpMyAdmin-ல் தேவையற்ற DB நீக்குவது — பாதுகாப்பான நடைமுறை

Database பட்டியலில் தெரியாத schema-வை “தேவையில்லை” என்று ஊகிக்க வேண்டாம். முதலில் owner, project, data மற்றும் backup/export-ஐ உறுதி செய்யவும். புதிய target DB-ல் migrations/seed/login சரியாக இருப்பதைச் சோதிக்கும் வரை பழைய active `school_management_new`-ஐயும் வேறு schema-களையும் நீக்க வேண்டாம். தற்போது எந்த database-யும் நாங்கள் நீக்கவில்லை.

உறுதிப்படுத்தப்பட்ட தேவையற்ற **ஒரே** schema-ஐ மட்டும் phpMyAdmin-ல் select செய்து **Operations → Drop the database** பயன்படுத்தலாம்; அல்லது அந்த database பெயரை மாற்றி மட்டும்:

```sql
DROP DATABASE `replace_with_confirmed_unused_database_name`;
```

ஒவ்வொரு schema-க்கும் முன் backup எடுக்கவும்; ஒரே நேரத்தில் பல database-களை drop செய்யும் loop/bulk command பயன்படுத்த வேண்டாம். `information_schema`, `mysql`, `performance_schema`, `sys` system schemas-ஐ ஒருபோதும் நீக்க வேண்டாம்; `phpmyadmin` schema-யையும் அதன் பயன்பாடு உறுதியில்லாமல் நீக்க வேண்டாம். சந்தேகம் இருந்தால் நீக்காமல் விடுங்கள்.

## 9. Team ownership மற்றும் ZIP

Module பிரிவு [IGK_ERP_TEAM_MODULE_OWNERSHIP_MATRIX.md](./IGK_ERP_TEAM_MODULE_OWNERSHIP_MATRIX.md)-ல். ZIP-ல் source, manifests, `.env.example`, guides, migrations, seeders, tests மட்டும் சேர்க்கவும். `.env`, DB dump, `vendor`, `node_modules`, `public\build`, `storage\logs`, generated cache-ஐ சேர்க்க வேண்டாம்.
