# Deployment Guide & Runbook (deploy.md v2)

**Project:** IGK School ERP — Laravel 12.70.0  
**SOP Reference:** §27 Deployment SOP  
**Owner:** D6 (QA/Documentation/Release)  
**Executed by:** D1 (Day 44 rehearsal #2)  
**Target tag:** v1.0.0  

---

## 1. Target Environment

| Component | Requirement |
|---|---|
| Server | Ubuntu 22.04 LTS · 2 vCPU · 4 GB RAM · 40 GB SSD · daily off-server backup target |
| Web | Nginx, document root `/var/www/sms/current/public`, gzip on, `client_max_body_size 8M` |
| PHP | 8.3 FPM with bcmath, ctype, curl, dom, fileinfo, gd, mbstring, openssl, pdo_mysql, tokenizer, xml, zip · `memory_limit 256M` · `upload_max_filesize 6M` · `post_max_size 8M` · OPcache enabled |
| Database | MySQL 8.0, utf8mb4_unicode_ci, bound to 127.0.0.1, dedicated app user with DML-only grants |
| Process management | systemd unit `sms-queue.service` running `queue:work --tries=3 --max-time=3600`; cron entry for `schedule:run` every minute |
| TLS | Let's Encrypt with auto-renew; HTTP→HTTPS redirect; HSTS after a week of stable operation |

---

## 2. Pre-deployment Checklist (every line initialled by D6 and D1)

1. [ ] Release tag created from `main`; release notes written.
2. [ ] Full automated suite green on the tagged commit; regression pack executed; zero open Critical/High.
3. [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` correct, unique `APP_KEY` generated for production and stored in the credential record.
4. [ ] Database credentials, mail credentials and backup credentials set in `.env`; `.env` permissions `600`, owned by the deploy user, not in Git.
5. [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `FILESYSTEM_DISK` configured with a private disk.
6. [ ] Telescope absent from production dependencies (`composer install --no-dev`); debug packages verified absent.
7. [ ] Storage: `php artisan storage:link`; `storage/` and `bootstrap/cache/` writable by the web user; no execute permission on the uploads directory.
8. [ ] Database backup of the current production data taken and verified (for re-deploys).
9. [ ] Migrations reviewed for destructive operations; a rollback plan written for this specific release.
10. [ ] Maintenance window agreed with the PO; users notified.
11. [ ] Rollback artefacts ready: previous release directory retained, DB dump on hand.

---

## 3. Deployment Steps

Run on the production server as the deploy user:

```bash
# 1. Put site into maintenance
php artisan down --render="errors::503" --retry=60

# 2. Fetch tags and checkout the release into a new release directory
git fetch --tags && git checkout v1.0.0

# 3. Install production dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Build Vite assets
npm ci && npm run build

# 5. Link shared env and storage
cp shared/.env . && ln -s shared/storage storage

# 6. Run migrations (reviewed migrations only)
php artisan migrate --force

# 7. Seed production roles, permissions, settings, grade scale, ONE super admin
php artisan db:seed --class=ProductionSeeder --force

# 8. Cache config, routes, views
php artisan config:cache && php artisan route:cache && php artisan view:cache

# 9. Storage link
php artisan storage:link

# 10. Symlink current release and reload web/PHP
ln -sfn releases/<new> current && systemctl reload php8.3-fpm nginx

# 11. Restart queue worker so it runs the new code
systemctl restart sms-queue.service

# 12. Bring site back up
php artisan up

# 13. Verify (§4). If any check fails → rollback (§5).
```

> **ProductionSeeder never seeds operational/demo data.** It seeds roles/permissions, settings, the grade scale, and one Super Admin. The staging/demo `DatabaseSeeder` is separate. A test asserts ProductionSeeder creates no students, invoices or marks.

---

## 4. Post-deployment Verification (all must pass within 20 minutes)

1. [ ] Home page returns 200 over HTTPS; certificate valid; HTTP redirects to HTTPS.
2. [ ] Super Admin logs in; forced password change works; the password is then rotated and recorded.
3. [ ] One smoke item per module: create a department · admit a test student · place them in a section · create an attendance session and save it · create an exam and enter one mark · generate and publish a result · generate an invoice · record a payment and open the receipt PDF · publish an announcement · open each of the 11 reports · open the audit log.
4. [ ] Queue: dispatch a test notification; confirm the worker processes it; `failed_jobs` empty.
5. [ ] Scheduler: confirm `schedule:list` and that the cron entry fires.
6. [ ] Mail: send a password reset to a real inbox; confirm delivery and link validity.
7. [ ] Error pages: request a non-existent route (custom 404) and an unauthorized route (custom 403); confirm no stack traces are shown.
8. [ ] Logs: `laravel.log` has no errors from the smoke run; log rotation configured.
9. [ ] Backup: run the backup command manually; confirm the artefact exists off-server.
10. [ ] Delete the test data created during smoke (documented as step 10 of the runbook).

---

## 5. Rollback

If verification fails:

```bash
php artisan down
# repoint the current symlink to the previous release
ln -sfn releases/<previous> current
# restore the pre-deployment database dump if migrations ran
# (restore command per backup-restore.md)
php artisan config:clear && php artisan route:clear && php artisan view:clear
systemctl reload php8.3-fpm nginx
systemctl restart sms-queue.service
php artisan up
```

Then verify with the smoke checklist (§4) and write an incident note the same day.

**Target: under 15 minutes, rehearsed on Day 44.**

---

## 6. Rehearsal Records

| Rehearsal | Date | Executed by | Result | Duration |
|---|---|---|---|---|
| Rehearsal #1 | Day 26 | D6 (runbook author) | Passed | ~25 min |
| Rehearsal #2 | Day 44 | D1 (did not write runbook) | Passed | ~28 min |

Runbook v2 corrects all errors found during rehearsals. Execute in under 30 min by someone who did not write it.

---

## 7. Related Documents

- `docs/guides/backup-restore.md` — nightly backup & restore drill procedure
- `docs/guides/environment.md` — every `.env` key and its production value source
- `docs/guides/install.md` — local setup from zero to a seeded working app
- SOP §27 — Deployment SOP (authoritative)
