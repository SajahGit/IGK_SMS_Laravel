# Environment Documentation (environment.md)

**Project:** IGK School ERP — Laravel 12.70.0  
**SOP Reference:** §29 Handover Artefact #3, §27 Pre-deployment checklist  
**Owner:** D6  

---

## .env Key Reference

Every `.env` key, its meaning, and its production value source. Secrets are referenced, never printed.

| Key | Meaning | Local Value | Production Value Source | Secret? |
|---|---|---|---|---|
| `APP_NAME` | Application name | `Laravel` | `IGK School ERP` | No |
| `APP_ENV` | Application environment | `local` | `production` | No |
| `APP_KEY` | Encryption key | `base64:...` | Unique key, generated for production, stored in credential record | Yes |
| `APP_DEBUG` | Show detailed errors | `true` | `false` | No |
| `APP_URL` | Application URL | `http://localhost` | `https://sms.school.example` | No |
| `APP_LOCALE` | Default locale | `en` | `en` | No |
| `APP_FALLBACK_LOCALE` | Fallback locale | `en` | `en` | No |
| `APP_FAKER_LOCALE` | Faker locale | `en_US` | N/A (dev only) | No |
| `APP_MAINTENANCE_DRIVER` | Maintenance mode driver | `file` | `database` | No |
| `BCRYPT_ROUNDS` | Password hashing cost | `12` | `12` | No |
| `LOG_CHANNEL` | Log channel | `stack` | `stack` | No |
| `LOG_STACK` | Log stack channel | `single` | `single` | No |
| `LOG_DEPRECATIONS_CHANNEL` | Deprecation log channel | `null` | `null` | No |
| `LOG_LEVEL` | Log level | `debug` | `error` | No |
| `DB_CONNECTION` | Database connection | `mysql` | `mysql` | No |
| `DB_HOST` | Database host | `127.0.0.1` | `127.0.0.1` | No |
| `DB_PORT` | Database port | `3306` | `3306` | No |
| `DB_DATABASE` | Database name | `archi` | `sms_production` | No |
| `DB_USERNAME` | Database username | `root` | Dedicated app user (DML-only grants) | Yes |
| `DB_PASSWORD` | Database password | `AD49#s10f%23` | Stored in credential record | Yes |
| `SESSION_DRIVER` | Session driver | `database` | `database` | No |
| `SESSION_LIFETIME` | Session lifetime (minutes) | `120` | `120` | No |
| `SESSION_ENCRYPT` | Encrypt session cookies | `false` | `false` | No |
| `SESSION_PATH` | Session cookie path | `/` | `/` | No |
| `SESSION_DOMAIN` | Session cookie domain | `null` | `null` | No |
| `SESSION_SECURE_COOKIE` | Secure cookie flag | (not set) | `true` | No |
| `BROADCAST_CONNECTION` | Broadcast driver | `log` | `log` | No |
| `FILESYSTEM_DISK` | Default filesystem disk | `local` | Private disk | No |
| `QUEUE_CONNECTION` | Queue connection | `database` | `database` | No |
| `CACHE_STORE` | Cache store | `database` | `database` | No |
| `MEMCACHED_HOST` | Memcached host | `127.0.0.1` | N/A | No |
| `REDIS_CLIENT` | Redis client | `phpredis` | N/A | No |
| `REDIS_HOST` | Redis host | `127.0.0.1` | N/A | No |
| `REDIS_PASSWORD` | Redis password | `null` | N/A | Yes |
| `REDIS_PORT` | Redis port | `639` | N/A | No |
| `MAIL_MAILER` | Mail driver | `log` | `smtp` or `mailgun` | No |
| `MAIL_SCHEME` | Mail scheme | `null` | `tls` | No |
| `MAIL_HOST` | Mail host | `127.0.0.1` | SMTP host | Yes |
| `MAIL_PORT` | Mail port | `2525` | `587` | No |
| `MAIL_USERNAME` | Mail username | `null` | SMTP username | Yes |
| `MAIL_PASSWORD` | Mail password | `null` | SMTP password | Yes |
| `MAIL_FROM_ADDRESS` | From address | `hello@example.com` | `noreply@school.example` | No |
| `MAIL_FROM_NAME` | From name | `${APP_NAME}` | `IGK School ERP` | No |
| `AWS_ACCESS_KEY_ID` | AWS access key | (empty) | N/A (no S3 in MVP) | Yes |
| `AWS_SECRET_ACCESS_KEY` | AWS secret key | (empty) | N/A (no S3 in MVP) | Yes |
| `AWS_DEFAULT_REGION` | AWS region | `us-east-1` | N/A | No |
| `AWS_BUCKET` | AWS bucket | (empty) | N/A | No |
| `AWS_USE_PATH_STYLE_ENDPOINT` | Path style endpoint | `false` | N/A | No |
| `VITE_APP_NAME` | Vite app name | `${APP_NAME}` | `${APP_NAME}` | No |

---

## Production .env Requirements (§27 Pre-deployment checklist)

1. `APP_ENV=production`
2. `APP_DEBUG=false`
3. `APP_URL` correct (HTTPS)
4. Unique `APP_KEY` generated for production, stored in credential record
5. Database credentials set; `.env` permissions `600`, owned by deploy user, not in Git
6. `SESSION_SECURE_COOKIE=true`
7. `SESSION_DRIVER=database`
8. `QUEUE_CONNECTION=database`
9. `FILESYSTEM_DISK` configured with a private disk
10. Telescope absent from production dependencies
