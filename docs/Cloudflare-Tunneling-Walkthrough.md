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

## Step 1: Start the Laravel Server

In **Terminal 1**:

```bash
php artisan serve
```

Verify it runs on `http://localhost:8000`.

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

| Issue | Cause | Resolution |
|---|---|---|
| **502 Bad Gateway** | Laravel server not started before tunnel | Run `php artisan serve` in a separate terminal first |
| **500 Internal Server Error** | MySQL not running (session driver = `database`) | Run `sudo /opt/lampp/lampp start` |
| **`classList` null error** | Assets built with `npm run dev` (Vite dev server on port 5173 not proxied by tunnel) | Run `npm run build` instead |
| **Mixed Content (assets)** | `ASSET_URL` not set to HTTPS | Add `ASSET_URL=https://<tunnel-url>` to `.env` and rebuild |
| **Mixed Content (XHR/navigation)** | Laravel doesn't trust Cloudflare proxy headers — generates `http://` URLs despite `APP_URL=https://` | Add `trustProxies` config in `bootstrap/app.php` |

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

# Start Laravel server
php artisan serve

# Start quick tunnel
cloudflared tunnel --url http://localhost:8000

# After tunnel URL changes — update .env, then:
php artisan config:clear && npm run build

# Stop tunnel
Ctrl+C
```
