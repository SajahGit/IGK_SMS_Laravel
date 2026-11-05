# deploy/

Server-side files for the SOP §27 deployment. Nothing here is loaded by the Laravel app.

| File | Install to | Purpose |
|---|---|---|
| nginx-sms.conf | /etc/nginx/sites-available/sms | HTTPS vhost, 8M upload limit |
| sms-queue.service | /etc/systemd/system/ | queue worker (`--tries=3 --max-time=3600`) |
| sms-cron | `crontab -u www-data` | scheduler every minute + 02:00 backup |
| backup.sh | run by cron | DB dump + storage archive, 14-day retention |

Encrypt and copy backups off-server as described in docs/guides/backup-restore.md.
