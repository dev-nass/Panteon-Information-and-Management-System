# Gmail SMTP Hybrid Plan — Hosting-Agnostic (VPS + Laravel Cloud)

> **Option C — Gmail SMTP** with free delivery to real inboxes (not Mailtrap). Hybrid resilience: **queue retry + failover log + user error only on dispatch failure**. Works identically on bare VPS and Laravel Cloud. This plan supersedes local Mailtrap setup at `.env:65` `sandbox.smtp.mailtrap.io:2525`.

---

## 1. Goal & Scope

**Goal:** Deliver transactional mail (password reset + clerk invitation) via free Gmail SMTP (`smtp.gmail.com:587`) that works on both a cheap VPS and Laravel Cloud without host-specific code branches.

**Scope includes:**
- Gmail account/App Password setup (you create `panteon.system@gmail.com`)
- Code changes to queued mailables + failover mailer
- Hosting-agnostic `database` queue
- VPS Supervisor + Cloud Worker playbooks

**Non-goals:** Custom domain SPF/DKIM (Brevo/Resend), bulk marketing, >500/day scale.

---

## 2. Current State (verified)

| Area | Current | Reference |
|------|---------|-----------|
| Laravel | v12, PHP 8.2+, Inertia v2 | `composer.json:14`, `bootstrap/app.php:28` `trustProxies` |
| Mail config | `smtp` + `failover` (smtp→log) defined but unused, `timeout:null` (30s) | `config/mail.php:40`, `config/mail.php:82`, `config/mail.php:48` |
| Env | Mailtrap `sandbox.smtp.mailtrap.io:2525`, `MAIL_MAILER=smtp` | `.env:64-68`, `.env.example:50-57` |
| Mailables | `PasswordResetMail` + `ClerkInvitationMail` import `ShouldQueue` but don't implement → sync sends | `app/Mail/PasswordResetMail.php:14`, `app/Mail/ClerkInvitationMail.php:13` |
| From address | Hard-coded `panteon@gmail.com` | `app/Mail/PasswordResetMail.php:32` |
| Controllers | `Mail::to()->send()` blocks HTTP | `app/Http/Controllers/Auth/PasswordResetController.php:50`, `app/Http/Controllers/Admin/ClerkInvitationController.php:82` |
| Queue | `QUEUE_CONNECTION=database` configured, `jobs`/`failed_jobs` tables exist but unused for mail | `.env:43`, `config/queue.php:16`, `config/queue.php:38`, `database/migrations/0001_01_01_000002_create_jobs_table.php:14` |
| Tests | `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync` | `phpunit.xml:28-29` |
| Dev queue | `queue:listen` already in `composer run dev` | `composer.json:61` |

---

## 3. Architecture Decision

### Chosen: Gmail SMTP + `ShouldQueue` + `database` queue + `failover` (hybrid)

**Why:**
- Sync blocks HTTP 0.8–3s per Gmail TLS handshake; with `timeout:null` can hang 30s → FPM worker exhaustion (VPS 5–10 workers) and Cloud 30s timeout.
- `database` driver is only driver needing no extra infra (no Redis/SQS) — works on VPS MySQL (`DB_CONNECTION=mysql` at `.env:26`) and Cloud MySQL.
- Hybrid = fast UX + retry + never-blocks-HTTP + log. Same code, only babysitter differs.

**Flow:**
```
HTTP POST /forgot-password
  -> DB insert password_reset_tokens (PasswordResetController.php:40)
  -> Mail::send(ShouldQueue) -> INSERT jobs (<100ms) -> 302 redirect (user done)
                                   |
                              [Worker] php artisan queue:work polls jobs
                                   -> TLS to smtp.gmail.com:587 -> success → delete job
                                   -> fail → retry 3x /90s (config/queue.php:43) → failover log → failed_jobs (config/queue.php:123)
```

**Worker vs Babysitter clarifications:**
- **Worker** = `php artisan queue:work` PHP process that drains `jobs`.
- **Supervisor (VPS)** = Linux daemon (`apt install supervisor`, Python) that keeps `queue:work` alive after reboot/crash. Not a composer package.
- **Cloud Worker (Laravel Cloud)** = Managed Supervisor in dashboard UI — same command, auto-restart on deploy. Code identical; only keeper differs.
- `queue:listen` (at `composer.json:61`) = dev mode, spawns fresh process per job (slower, reloads code).

**Alternatives rejected:**
- Pure sync: 0 RAM/daemon but loses mail on Gmail blip after DB write.
- Redis: needs extra service, not portable.
- Env-flag dual path: 2 code paths to test.

---

## 4. Implementation Steps

### Phase A — Google Account (you, 5 min, outside Laravel)

1. Create new Gmail `panteon.system@gmail.com` (or chosen name).
2. Enable 2-Step Verification (Google Account > Security).
3. Create App Password (Security > App Passwords > `Panteon System`) → copy 16-char key, remove spaces (`abcd efgh ijkl mnop` → `abcdefghijklmnop`).
4. Store only in `.env` / Cloud Secrets, never commit.

### Phase B — Code Changes (single branch for both hosts)

**1. `.env` + `.env.example:50-57`:**
```env
MAIL_MAILER=failover
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=panteon.system@gmail.com
MAIL_PASSWORD=abcdefghijklmnop
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=panteon.system@gmail.com # MUST == MAIL_USERNAME for Gmail
MAIL_FROM_NAME="Panteon De Dasmariñas"
MAIL_SCHEME=null
QUEUE_CONNECTION=database
```

**2. `config/mail.php:48`:**
```php
'timeout' => 10, // was null (30s hang) → fail faster
// keep failover at config/mail.php:82:
'failover' => ['transport'=>'failover','mailers'=>['smtp','log'],'retry_after'=>60],
```

**3. `app/Mail/PasswordResetMail.php:14,29-35`:**
```php
use Illuminate\Contracts\Queue\ShouldQueue;
class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Password Reset Mail',
        );
    }
}
```

**4. `app/Mail/ClerkInvitationMail.php:13,27-30`:**
```php
class ClerkInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'You are invited to register as a Clerk'
        );
    }
}
```

**5. `app/Http/Controllers/Auth/PasswordResetController.php:50`:**
```php
use Illuminate\Support\Facades\Log;
try {
    Mail::to($request->email)->send(new PasswordResetMail($code));
} catch (\Throwable $e) {
    Log::error('Mail queue dispatch failed', ['email'=>$request->email, 'e'=>$e->getMessage()]);
    return back()->withErrors(['email'=>'Could not queue email, please try again.']);
}
```

**6. `app/Http/Controllers/Admin/ClerkInvitationController.php:82`:**
```php
use Illuminate\Support\Facades\Log;
try {
    Mail::to($request->email)->send(new ClerkInvitationMail($url));
} catch (\Throwable $e) {
    Log::error('Mail queue dispatch failed', ['email'=>$request->email, 'e'=>$e->getMessage()]);
    return back()->withErrors(['email'=>'Could not queue email, please try again.']);
}
```
Note: `catch` covers dispatch failure only (DB down). Async Gmail failures are retried in worker → `failed_jobs` + log via `failover`, not user 500.

### Phase C — Deploy: VPS (Babysitter = Supervisor)

```bash
git pull && composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan config:cache && php artisan queue:restart

# one-time Supervisor install
sudo apt update && sudo apt install -y supervisor

# /etc/supervisor/conf.d/panteon-worker.conf
[program:panteon-worker]
command=php /var/www/panteon/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --queue=default
directory=/var/www/panteon
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/panteon-worker.log
stopwaitsecs=3600

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start panteon-worker:*
sudo supervisorctl status

# verify
telnet smtp.gmail.com 587  # expect 220; 25 blocked is ok
php artisan tinker --execute 'Mail::raw("VPS test", fn($m)=>$m->to("you@gmail.com")->subject("VPS Gmail ok"));'
php artisan queue:failed
tail -f storage/logs/laravel.log
```

### Phase D — Deploy: Laravel Cloud (Babysitter = Managed Worker)

1. `git push cloud main` (same branch as VPS).
2. Dashboard > Environment > Secrets: set identical `MAIL_*` + `QUEUE_CONNECTION=database` + `MAIL_FROM_*`.
3. Dashboard > Workers > New Worker:
   - Command: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --queue=default`
   - Processes: 1
   - Auto-restart on deploy: enabled (Cloud runs `queue:restart` automatically).
4. Verify via Cloud Console > Tinker:
   ```
   Mail::raw("Cloud test", fn($m)=>$m->to("you@gmail.com")->subject("Cloud Gmail ok"));
   ```
5. Dashboard > Workers > Logs = `panteon-worker.log` equivalent; `queue:failed` via Cloud artisan.

Local dev: stays `QUEUE_CONNECTION=database` (or `sync`); `composer run dev` already runs `queue:listen` at `composer.json:61`.

---

## 5. Files to Change

| File | Lines | Change |
|------|-------|--------|
| `.env` | `64-69` | Mailtrap → Gmail `failover` vars |
| `.env.example` | `50-57` | Mirror for docs/onboarding |
| `config/mail.php` | `48` | `timeout:null` → `10` |
| `app/Mail/PasswordResetMail.php` | `14,32` | `implements ShouldQueue` + `config('mail.from.address')` |
| `app/Mail/ClerkInvitationMail.php` | `13,27` | `implements ShouldQueue` + envelope `from` |
| `app/Http/Controllers/Auth/PasswordResetController.php` | `50` | `try/catch` dispatch |
| `app/Http/Controllers/Admin/ClerkInvitationController.php` | `82` | `try/catch` dispatch |

**Unchanged:** `config/queue.php:38` database driver, `phpunit.xml:28` `array`/`sync`, `bootstrap/app.php:28`, migrations.

---

## 6. Verification Checklist

- [ ] `php artisan config:show mail` shows `smtp.gmail.com:587/tls` + `failover` on both hosts
- [ ] `php artisan tinker` `Mail::raw` arrives in real inbox + Gmail Sent folder; check Spam once
- [ ] `POST /forgot-password` → <200ms, row appears then disappears in `jobs`, `failed_jobs` empty
- [ ] `POST /admin/clerk-invitations` → same, invitation email received
- [ ] Simulate Gmail failure (bad App Password) → job retries 3x per `config/queue.php:43`, then `failed_jobs` + `storage/logs/laravel.log` via `failover`, user still got 302 not 500
- [ ] `php artisan test --compact` green (uses `Mail::fake()`, assert `Mail::assertQueued(PasswordResetMail::class)` if `ShouldQueue`)
- [ ] VPS: `supervisorctl status` `RUNNING`, survives `sudo reboot`; Cloud: Worker shows Running in dashboard
- [ ] `GET /up` (at `bootstrap/app.php:16`) still 200 even if queue down

---

## 7. Hybrid Failure & Observability

| Scenario | What user sees | What ops sees | Recovery |
|----------|----------------|---------------|----------|
| Gmail slow (2s) | Instant 302 (queue) | Worker log shows 2s send | None |
| Gmail quota 500/day or bad password | Instant 302, then retry 3x → log | `failed_jobs` + `laravel.log` via `failover` | `php artisan queue:retry all` after fixing password; or `MAIL_MAILER=log` fallback ensures no 500 |
| DB down (can't queue) | `withErrors('Could not queue')` | `Log::error` at controller | Fix DB, user retries |
| Worker not running | Instant 302 but mail sits in `jobs` | `jobs` count >0, `queue:failed` | Start Supervisor/Cloud Worker |

Monitor: `php artisan queue:failed`, `tail -f storage/logs/laravel.log`, Supervisor `stdout_logfile`, Cloud Worker Logs.

---

## 8. Security & Limits

- App Password 16-char → `.env` / Cloud Secrets only, gitignored (`.env` already ignored).
- `MAIL_FROM_ADDRESS` must equal `MAIL_USERNAME` or Gmail rewrites `From` → Spam.
- Gmail shows `via gmail.com`, not custom domain — acceptable for transactional; note migration path to Resend (`config/mail.php:64`, `config/services.php:21`) via 1-line `MAIL_MAILER=resend` + `RESEND_API_KEY` swap, no code change, when volume/branding needs grow.
- Limit 500/day — Panteon transactional (<20/day) safe.

---

## 9. Rollback

1. Revert `.env` to Mailtrap or `MAIL_MAILER=log` at `.env:55` (original commented).
2. Remove `implements ShouldQueue` from both mailables.
3. `php artisan config:clear && php artisan queue:clear`.
4. Stop Supervisor: `sudo supervisorctl stop panteon-worker:*` or remove Cloud Worker.

---

## 10. Alternatives Considered

| Alternative | Tradeoff |
|-------------|----------|
| **Pure sync (no queue)** | 0 RAM/daemon, simplest VPS deploy, but blocks HTTP, 500 on Gmail blip after DB write — poor UX at scale. |
| **Redis queue** | Faster but needs Redis install on VPS + Cloud Redis add-on — not portable. |
| **Brevo/Resend free tier** | Better deliverability with domain, but requires domain/DNS, not "free Gmail" brief. Kept as Phase 2. |

---

## 11. Effort

~2h code + 1h per host deploy. No downtime, backward-compatible with existing `phpunit.xml:29` sync tests.

---

## 12. Open Questions Resolved

1. Gmail account: **you will create new `panteon.system@gmail.com`** — plan uses `config('mail.from.address')` so rotation is env-only.
2. Supervisor vs Cloud Worker: **same `queue:work`, different babysitter** — Supervisor = Linux daemon you install on VPS; Cloud Worker = managed Supervisor in Cloud UI.
3. Hybrid: **combined** — queue retry + failover log + hard error only on dispatch failure.

---

## 13. Next Actions (after approval)

1. Save this file to `docs/todo/gmail-smtp-hybrid-plan.md` (done).
2. Lift plan mode → apply Phase B code edits.
3. Run `vendor/bin/pint --dirty --format agent` per project rule.
4. Deploy Phase C/D per target host.
5. Execute verification checklist.

