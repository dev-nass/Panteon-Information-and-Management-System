# Backup Retention Explained

> Simple explanation of how the current backup delete rules work.

## What does "days, weeks, months, years" mean?

It means **how old the backup file is** (how many days since it was created).

- Today (Sep 14) = 0 days old
- Yesterday (Sep 13) = 1 day old
- Sep 7 = 7 days old
- Aug 15 = 30 days old

## How the current system works

File: `config/backup.php:335-362`

It does **not** delete everything after 7 days. It thins out gradually:

| Age of backup | What happens |
|---|---|
| **0-7 days old** | Keep everything. All backups stay. |
| **8-37 days old** | Keep 1 per day. If there are 2 backups on the same day, delete the extra. |
| **38-94 days old** | Keep 1 per week. |
| **94-214 days old** | Keep 1 per month. |
| **214-944 days old** | Keep 1 per year. |
| **After 2 years** | Delete everything except the newest file. |

### Example (daily backup at 01:30)

If today is Sep 14:

- Sep 14, 13, 12, 11, 10, 9, 8, 7 (0-7 days old) = all kept
- Sep 6 (8 days old) = kept, but if you made 2 backups that day, 1 is deleted
- Aug 10 (35 days old) = still kept
- June 10 (96 days old) = still kept, but only 1 from that week remains
- Jan 10 (8 months old) = still kept, but only 1 from that month remains

This is why backups currently live for months/years.

## What you requested

**Only live for 7 days then permanently gone.**

- Sep 7 to Sep 14 = kept
- Sep 6 and older = automatically deleted forever (via `backup:clean`)

To achieve this, the weekly/monthly/yearly keep values need to be set to `0` so there are no age brackets — after 7 days, delete.

## Where it runs

- Auto backup: `routes/console.php:12` at 01:30 daily
- Auto cleanup: `routes/console.php:11` at 01:00 daily
- Storage: `storage/app/backups` (`config/filesystems.php:50`)
- Requires server cron: `* * * * * php artisan schedule:run`
