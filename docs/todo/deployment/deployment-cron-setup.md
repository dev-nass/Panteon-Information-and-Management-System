# Deployment & Cron Job Setup Guide

## Context

The Laravel scheduler is defined in `routes/console.php` but requires a system-level cron entry to execute automatically. Without it, scheduled tasks (like daily database backups) will never run. This guide covers setup for all major hosting environments, with a focus on Hostinger.

---

## Hosting Comparison

| Hosting | Cron Setup | What You Need to Do |
|---|---|---|
| **Laravel Cloud** | Automatic — Laravel Cloud handles scheduling natively | Nothing — it just works |
| **Laravel Forge / Ploi** | Automatic — these tools add the cron entry during server provisioning | Nothing — just make sure "Schedule" is enabled in the dashboard |
| **VPS (DigitalOcean, Linode, AWS EC2)** | Manual — you set up the cron via SSH | Add the cron entry yourself or include it in a setup script |
| **Hostinger (Shared Hosting)** | Manual — set up via hPanel UI | Add the cron job through hPanel → Advanced → Cron Jobs |
| **Hostinger (VPS)** | Manual — full SSH access like any VPS | Add the cron entry via `crontab -e` |
| **Shared hosting (cPanel, etc.)** | Varies — some support cron, some don't | Check if your host allows cron jobs; add via cPanel if available |
| **Docker** | Manual — the cron daemon must run inside the container | Add cron to your Dockerfile or use a process manager like Supervisord |

---

## Hostinger Setup (Primary Target)

### Hostinger hPanel (Shared Hosting)

1. Log in to **hPanel**
2. Navigate to **Advanced → Cron Jobs**
3. Add a new cron job:
   - **Interval**: Every minute
   - **Command**:
     ```
     /opt/alt/php82/usr/bin/php /home/your_username/domains/yourdomain.com/public_html/artisan schedule:run >> /dev/null 2>&1
     ```
4. Save

### Finding Your PHP Path on Hostinger

SSH into your Hostinger server and run:

```bash
which php
```

Common Hostinger paths:
- `/opt/alt/php82/usr/bin/php` (PHP 8.2 — Hostinger default)
- `/opt/alt/php83/usr/bin/php` (PHP 8.3)

### Finding Your Project Path on Hostinger

The standard Hostinger directory structure:

```
/home/your_username/
├── domains/
│   └── yourdomain.com/
│       └── public_html/          ← web root
│           ├── artisan           ← this is what the cron needs
│           ├── routes/
│           ├── app/
│           └── ...
```

So the full artisan path is:

```
/home/your_username/domains/yourdomain.com/public_html/artisan
```

If your project is in a subdirectory (e.g., `public_html/project/`), adjust accordingly:

```
/home/your_username/domains/yourdomain.com/public_html/project/artisan
```

### Shell Script Workaround

Hostinger's cron UI sometimes doesn't handle special characters well. If the direct command fails, create a shell script:

```bash
#!/bin/bash
cd /home/your_username/domains/yourdomain.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```

1. Save this as `cron.sh` in your project root
2. Make it executable: `chmod +x cron.sh`
3. Point the Hostinger cron to: `/bin/bash /home/your_username/domains/yourdomain.com/public_html/cron.sh`

### Verify It Works

1. **Test via SSH first** — run the command manually before adding to hPanel:
   ```bash
   /opt/alt/php82/usr/bin/php /home/your_username/domains/yourdomain.com/public_html/artisan schedule:run
   ```
2. **Check the logs** after the next scheduled run:
   ```bash
   cat storage/logs/laravel.log | tail -50
   ```
3. **List registered schedules**:
   ```bash
   php artisan schedule:list
   ```

### Common Pitfalls on Hostinger

| Issue | Cause | Fix |
|---|---|---|
| **Wrong path** | Most common issue — the artisan path doesn't match the actual directory | Verify via SSH: `ls /home/your_username/domains/yourdomain.com/public_html/artisan` |
| **Permission denied** | PHP binary or artisan not executable | Run `chmod +x artisan` and ensure PHP binary is accessible |
| **Empty output** | Cron ran but no visible result | Check `storage/logs/laravel.log` for errors |
| **Schedule:list shows nothing** | `app/Console/Kernel.php` not loaded | In Laravel 12, schedules are in `routes/console.php` — no Kernel needed |
| **Cron not running at all** | Hostinger cron daemon may not be active | Contact Hostinger support to verify cron is enabled on your plan |

---

## Other Hosting Environments

### Laravel Cloud

No setup required. Laravel Cloud manages scheduling automatically. Just push your code and it works.

### Laravel Forge / Ploi

These tools provision the cron entry during server setup. Verify in the dashboard that "Schedule" is enabled. No manual configuration needed.

### VPS (DigitalOcean, Linode, AWS EC2)

1. SSH into the server
2. Add the cron entry:
   ```bash
   crontab -e
   ```
3. Add this line:
   ```
   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
   ```
4. Save and exit

### Docker

The cron daemon must run inside the container. Options:

1. **Add cron to your Dockerfile**:
   ```dockerfile
   RUN echo "* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/laravel
   RUN chmod 0644 /etc/cron.d/laravel
   RUN crontab /etc/cron.d/laravel
   ```

2. **Use Supervisord** to run both the web server and cron:
   ```ini
   [program:cron]
   command=/usr/sbin/cron -f
   autostart=true
   autorestart=true
   ```

### cPanel (Generic Shared Hosting)

1. Log in to cPanel
2. Navigate to **Advanced → Cron Jobs**
3. Set the interval to **Every Minute**
4. Add the command:
   ```
   /usr/bin/php /home/your_username/public_html/artisan schedule:run >> /dev/null 2>&1
   ```
5. Find your PHP path: run `which php` via SSH or cPanel Terminal

---

## Development vs Production

| Environment | Cron Setup | Testing |
|---|---|---|
| **Local development** | `crontab -e` on your machine, OR use `php artisan schedule:work` for real-time testing | Run `php artisan schedule:list` to verify |
| **Production (Hostinger)** | Add cron via hPanel → Advanced → Cron Jobs | Check `storage/logs/laravel.log` after first scheduled run |

The Laravel code (`routes/console.php`) stays the same in both environments. Only the cron entry setup differs.

---

## What's Scheduled in This Project

From `routes/console.php`:

```php
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
```

| Time | Command | What It Does |
|---|---|---|
| 01:00 daily | `backup:clean` | Prunes old backups per the retention policy in `config/backup.php` |
| 01:30 daily | `backup:run` | Creates a full backup (database + files) |

---

## Verification Checklist

After deploying to Hostinger:

- [ ] Project files are in the correct directory
- [ ] `.env` is configured with production database credentials
- [ ] `php artisan migrate` has been run
- [ ] `php artisan config:cache` and `php artisan route:cache` have been run
- [ ] PHP CLI path is correct (`which php` via SSH)
- [ ] Cron entry is added in hPanel → Advanced → Cron Jobs
- [ ] Cron is set to run every minute
- [ ] `php artisan schedule:list` shows the two backup tasks
- [ ] First scheduled run completes without errors (check `storage/logs/laravel.log`)
- [ ] Backups appear in the admin Database Backup page
