# Quick Tunnel Setup Walkthrough — Panteon IMS (Verified)

This document records the actual step-by-step process followed to set up and verify the Cloudflare Quick Tunnel for Panteon IMS, including all issues encountered and their resolutions.

> Refer to [Cloudflare-Tunneling-Setup.md](./Cloudflare-Tunneling-Setup.md) for the full reference guide.

---

## Prerequisites

### Install `cloudflared`

```bash
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb -o cloudflared.deb
sudo dpkg -i cloudflared.deb

# Verify
cloudflared --version
```

### Ensure MySQL is Running

The session driver is set to `database`, so MySQL must be running before starting the server.

```bash
sudo /opt/lampp/lampp start
```

---

## Step 1: Start the Laravel Server + Queue Worker

In **Terminal 1** (and **Terminal 1b** for queue if not using `composer run dev`):

```bash
# Option A (recommended) — starts serve + queue:listen + vite together:
composer run dev

# Option B — separate:
# Terminal 1: php artisan serve --host=0.0.0.0 --port=8000
# Terminal 1b: php artisan queue:work --sleep=3 --tries=3 --verbose
```

> **Since 2026-09-04:** Gmail mail is queued (`ShouldQueue`, `QUEUE_CONNECTION=database`). Without a running `queue:work`/`queue:listen`, password-reset and clerk-invite emails stay in `jobs` and won't send — keep the worker open while tunnel is up.

Verify `http://localhost:8000` loads and `php artisan queue:failed` shows 0.

---

## Step 2: Start the Quick Tunnel

In **Terminal 2**:

```bash
cloudflared tunnel --url http://localhost:8000
```

This generates a random `*.trycloudflare.com` URL.

---

## Step 3: Update `.env`

```env
APP_URL=https://<your-tunnel-url>.trycloudflare.com
ASSET_URL=https://<your-tunnel-url>.trycloudflare.com
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=null
```

> **Important:** Both `APP_URL` and `ASSET_URL` must be set to the same HTTPS tunnel URL.

---

## Step 4: Configure Trusted Proxies

Cloudflare terminates HTTPS and forwards plain HTTP to the local server. Laravel must be told to trust the proxy headers, otherwise Inertia/Axios generates `http://` URLs causing **Mixed Content** errors.

In `bootstrap/app.php`, add the `trustProxies` call inside the `withMiddleware` block:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        HandleInertiaRequests::class,
    ]);

    $middleware->alias([
        'clerk' => ClerkMiddleware::class,
        'admin' => AdminMiddleware::class,
    ]);

    // TUNNELING CONFIG
    $middleware->trustProxies(
        at: '**',
        headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
    );
})
```

---

## Step 5: Clear Config and Rebuild Assets

```bash
php artisan config:clear && npm run build
```

> **Note:** Use `npm run build`, not `npm run dev`. The Vite dev server runs on port 5173, which the tunnel does not proxy — resulting in broken assets and `classList` errors from Preline.

---

## Step 6: Verify

1. Open the tunnel URL in a browser.
2. Confirm assets load (no mixed content errors in console).
3. Test login and session functionality.

---

## Issues Encountered and Resolutions

| Issue                              | Cause                                                                                                | Resolution                                                                                                                                                      |
| ---------------------------------- | ---------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **502 Bad Gateway**                | Laravel server not started before tunnel                                                             | Run `php artisan serve` in a separate terminal first                                                                                                            |
| **500 Internal Server Error**      | MySQL not running (session driver = `database`)                                                      | Run `sudo /opt/lampp/lampp start`                                                                                                                               |
| **`classList` null error**         | Assets built with `npm run dev` (Vite dev server on port 5173 not proxied by tunnel)                 | Run `npm run build` instead                                                                                                                                     |
| **Mixed Content (assets)**         | `ASSET_URL` not set to HTTPS                                                                         | Add `ASSET_URL=https://<tunnel-url>` to `.env` and rebuild                                                                                                      |
| **Mixed Content (XHR/navigation)** | Laravel doesn't trust Cloudflare proxy headers — generates `http://` URLs despite `APP_URL=https://` | Add `trustProxies` config in `bootstrap/app.php`                                                                                                                |
| **Mail not received (queue)**      | `ShouldQueue` mail stays in `jobs` — no `queue:work` running (since 2026-09-04 Gmail hybrid)         | Run `composer run dev` (includes `queue:listen`) or `php artisan queue:work --sleep=3 --tries=3 --verbose` in second terminal; check `php artisan queue:failed` |

---

## Limitations

- **Quick tunnel URLs change on restart.** Each restart generates a new URL, requiring updates to `APP_URL`, `ASSET_URL`, and a rebuild.
- **Not suitable for production.** Quick tunnels are unauthenticated and publicly accessible.
- **For persistent URLs**, use a named tunnel (Option B) with a Cloudflare account and custom domain.

---

## Quick Reference (End-to-End)

```bash
# Start MySQL (if using LAMPP)
sudo /opt/lampp/lampp start

# Start Laravel server + queue worker (REQUIRED for Gmail mail — jobs stay queued without worker)
# Option A (recommended) — single command, includes queue:listen + serve + pail + vite:
composer run dev
# Option B — separate terminals:
# Terminal 1: php artisan serve --host=0.0.0.0 --port=8000
# Terminal 2: php artisan queue:work --sleep=3 --tries=3 --verbose  # keep open while tunnel is up

# Start quick tunnel (new terminal)
cloudflared tunnel --url http://localhost:8000

# After tunnel URL changes — update .env (APP_URL + ASSET_URL), then:
php artisan config:clear && npm run build

# Verify queue (while tunnel is up)
php artisan queue:failed  # expect 0; DB::table("jobs")->count() should be 0 after worker DONE

# Stop
Ctrl+C (tunnel), Ctrl+C (worker/serve)
```

> **Since 2026-09-04:** Mail (`PasswordResetMail`, `ClerkInvitationMail`) is `ShouldQueue` via `QUEUE_CONNECTION=database` + Gmail `failover` (`docs/todo/gmail-smtp-hybrid-plan.md`). Without a running `queue:work`/`queue:listen`, `forgot-password` and clerk invites will queue but not send.

## Tested on September 04

```bash
cloudflared tunnel --url http://localhost:8000
# update the .env

php artisan config:clear && npm run build

# term 1
php artisan serve
# term 2
php artisan queue:work --sleep=3 --tries=3 --verbose

# or but not working for me
composer run dev
```
