# Backup & Restore Procedure (backup-restore.md)

**Project:** IGK School ERP — Laravel 12.70.0  
**SOP Reference:** §27.6 Backup & restore, NFR-5, SEC-14  
**Owner:** D6  
**Drill date:** Day 46  

---

## 1. Backup Strategy

| Item | Schedule | Retention | Destination |
|---|---|---|---|
| Full MySQL dump | Nightly 02:00 | 14 days | Off-server (encrypted) |
| `storage/app` archive | Weekly | 14 days | Off-server (encrypted) |
| Weekly full copy | Weekly | 3 months | Off-server (encrypted) |

Failure of a backup run sends a notification to the admin email and appears on the dashboard.

**Restore is tested, not assumed.** Day 46: restore the most recent backup into a scratch database, run the smoke checklist against it, record the elapsed time.

---

## 2. Backup Commands

### 2.1 Full MySQL dump (nightly)

```bash
# Run as the backup user, from /var/www/sms/current
mysqldump -u sms_app -p'${DB_PASSWORD}' --single-transaction --routines --triggers sms > /backups/db/sms_$(date +%Y%m%d_%H%M%S).sql

# Encrypt the dump
openssl enc -aes-256-cbc -salt -in /backups/db/sms_$(date +%Y%m%d_%H%M%S).sql -out /backups/db/sms_$(date +%Y%m%d_%H%M%S).sql.enc -pass file:/etc/sms/backup.key

# Copy off-server (e.g. rsync to backup host)
rsync -avz /backups/db/sms_$(date +%Y%m%d_%H%M%S).sql.enc backup@offsite:/backups/sms/db/

# Remove local unencrypted dump after successful off-server copy
shred -u /backups/db/sms_$(date +%Y%m%d_%H%M%S).sql
```

### 2.2 storage/app archive (weekly)

```bash
# Run as the backup user
tar -czf /backups/files/storage_app_$(date +%Y%m%d).tar.gz -C /var/www/sms/current storage/app

# Encrypt
openssl enc -aes-256-cbc -salt -in /backups/files/storage_app_$(date +%Y%m%d).tar.gz -out /backups/files/storage_app_$(date +%Y%m%d).tar.gz.enc -pass file:/etc/sms/backup.key

# Copy off-server
rsync -avz /backups/files/storage_app_$(date +%Y%m%d).tar.gz.enc backup@offsite:/backups/sms/files/
```

### 2.3 Cron entry (root crontab)

```cron
# Nightly full DB dump at 02:00
0 2 * * * /usr/local/bin/sms-backup-db.sh >> /var/log/sms/backup.log 2>&1

# Weekly storage archive on Sundays at 03:00
0 3 * * 0 /usr/local/bin/sms-backup-files.sh >> /var/log/sms/backup.log 2>&1
```

---

## 3. Restore Procedure (Day 46 drill)

### 3.1 Restore database into a scratch database

```bash
# 1. Create scratch database
mysql -u root -p -e "CREATE DATABASE sms_scratch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Decrypt the most recent backup
openssl enc -d -aes-256-cbc -in /backups/db/sms_latest.sql.enc -out /tmp/sms_restore.sql -pass file:/etc/sms/backup.key

# 3. Restore into scratch database
mysql -u root -p sms_scratch < /tmp/sms_restore.sql

# 4. Clean up decrypted file
shred -u /tmp/sms_restore.sql
```

### 3.2 Run smoke checklist against restored copy

Point a staging vhost at the restored scratch database and run:

1. [ ] Home page returns 200
2. [ ] Super Admin logs in
3. [ ] One smoke item per module (create department, admit student, create exam, enter mark, publish result, generate invoice, record payment, open receipt, publish announcement, open reports, open audit log)
4. [ ] Queue worker processes a test notification; `failed_jobs` empty
5. [ ] Error pages return custom 404/403 without stack traces
6. [ ] `laravel.log` has no errors from the smoke run

### 3.3 Drill record

| Field | Value |
|---|---|
| Drill date | Day 46 |
| Backup restored | sms_latest.sql.enc (nightly, < 24h old) |
| Scratch database | sms_scratch |
| Restore elapsed time | _____ minutes |
| Smoke checklist result | _____ (pass/fail) |
| Smoke checklist duration | _____ minutes |
| Verified by | D6 |
| Sign-off | D1 |

**Acceptance:** Restored copy passes the smoke checklist. Drill evidence attached to this document.

---

## 4. Retention & Cleanup

```bash
# Remove backups older than 14 days (daily)
find /backups/db -name "*.sql.enc" -mtime +14 -delete
find /backups/files -name "*.tar.gz.enc" -mtime +14 -delete

# Keep weekly copies for 3 months
find /backups/files -name "*.tar.gz.enc" -mtime +90 -delete
```

---

## 5. Related Documents

- `docs/guides/deploy.md` — deployment runbook (§27)
- SOP §27.6 — Backup & restore (authoritative)
- SOP NFR-5 — Nightly full DB + weekly files, 14-day retention, restore tested
- SOP SEC-14 — Nightly DB + weekly storage via scheduler; 14-day retention; off-server copy; documented restore
