# Cloudflare Tunneling Setup Guide — Panteon Information and Management System

## Overview

This guide covers setting up Cloudflare Tunnel (`cloudflared`) to expose your local Laravel development environment to the internet. This is useful for testing with external collaborators, webhook integrations (e.g., payment gateways, OAuth callbacks), and mobile device testing.

---

## 1. Prerequisites

### Install `cloudflared`

```bash
# Ubuntu/Debian
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb -o cloudflared.deb
sudo dpkg -i cloudflared.deb

# macOS (Homebrew)
brew install cloudflare/cloudflare/cloudflared

# Verify installation
cloudflared --version
```

### Cloudflare Account

- A Cloudflare account is **not required** for quick tunnels (`trycloudflare.com`)
- For persistent/custom domain tunnels, you need a Cloudflare account with a registered domain

---

## 2. Two Tunneling Options

### Option A: Quick Tunnel (No Account Required)

Best for temporary testing sessions. Generates a random `*.trycloudflare.com` URL.

```bash
# Start the tunnel pointing to your local Laravel server
cloudflared tunnel --url http://localhost:8000
```

This will output something like:

```
Your quick Tunnel has been created! Visit it at:
https://random-name-here.trycloudflare.com
```

**Limitations:**
- URL changes every time you restart the tunnel
- Not suitable for webhooks or OAuth callbacks that require a fixed URL
- No custom domain support

### Option B: Named Tunnel (Cloudflare Account Required)

Best for persistent URLs and production-like testing.

```bash
# Login to Cloudflare
cloudflared tunnel login

# Create a named tunnel
cloudflared tunnel create panteon-dev

# Configure the tunnel (see Section 4)
# Point it to your local server

# Run the tunnel
cloudflared tunnel run panteon-dev
```

---

## 3. Environment Variable Changes

### Update `.env` for Tunneling

Before starting the tunnel, update the following variables in your `.env` file:

```env
# Change from localhost to your tunnel URL
APP_URL=https://your-tunnel.trycloudflare.com

# Set to false for testing with others (never expose debug info publicly)
APP_DEBUG=false

# Enable secure cookies (Cloudflare serves over HTTPS)
SESSION_SECURE_COOKIE=true

# Optionally set session domain to null (works across subdomains)
SESSION_DOMAIN=null

# Ensure APP_ENV is set appropriately
APP_ENV=local
```

### Why These Changes Matter

| Variable | Reason |
|---|---|
| `APP_URL` | Laravel generates URLs (redirects, asset links, email links) using this value. Must match the publicly accessible URL. |
| `APP_DEBUG=false` | Prevents sensitive stack traces and environment variables from being exposed to end users. |
| `SESSION_SECURE_COOKIE=true` | Ensures session cookies are only sent over HTTPS. Without this, sessions may not work or may be insecure. |

---

## 4. Named Tunnel Configuration (Optional)

If using a named tunnel, create a configuration file at `~/.cloudflared/config.yml`:

```yaml
tunnel: <TUNNEL_ID>
credentials-file: /home/<user>/.cloudflared/<TUNNEL_ID>.json

ingress:
  - hostname: panteon.yourdomain.com
    service: http://localhost:8000
    originRequest:
      noTLSVerify: true
  - service: http_status:404
```

### DNS Routing (Named Tunnels Only)

```bash
# Route DNS to your tunnel
cloudflared tunnel route dns panteon-dev panteon.yourdomain.com
```

---

## 5. Local Server Requirements

### Start Your Laravel Server

Ensure your local server is running before starting the tunnel:

```bash
# Option 1: Laravel built-in server
php artisan serve --port=8000

# Option 2: Using composer dev script (runs all services)
composer dev

# Option 3: Using LAMPP/XAMPP (if Apache is already running on port 80)
# Update the tunnel command to point to the correct port:
# cloudflared tunnel --url http://localhost
```

### Check Your Current Port

Your `.env` may have `APP_URL=http://localhost:8000`. If you're using LAMPP/Apache which typically runs on port 80, adjust accordingly:

```bash
# Check if port 8000 is in use
lsof -i :8000

# Check if port 80 is in use
lsof -i :80
```

---

## 6. Webhook and OAuth Considerations

If you're testing payment gateways (Stripe, PayMongo), OAuth providers, or other webhook-based services:

### Update Webhook URLs

Replace all `http://localhost:8000` references in third-party services with your tunnel URL:

```
https://your-tunnel.trycloudflare.com/api/webhooks/stripe
```

### CORS and Trusted Hosts

If you encounter CORS or trusted host issues, add your tunnel domain to `config/cors.php`:

```php
// config/cors.php
'allowed_origins' => [
    env('APP_URL', 'http://localhost'),
],
```

And in `AppServiceProvider.php`, ensure the URL is trusted:

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    $this->app['request']->setTrustedHosts([
        'your-tunnel.trycloudflare.com',
        'localhost',
    ]);
}
```

---

## 7. Security Considerations

### For Testing Only

- **Never use quick tunnels in production.** They are unauthenticated and publicly accessible.
- Disable `APP_DEBUG` when exposing to others — it leaks environment variables and database credentials.
- Use `SESSION_SECURE_COOKIE=true` to prevent session hijacking over HTTP.

### Rate Limiting

Cloudflare free tier includes basic DDoS protection. For testing purposes, this is sufficient. If you need to add rate limiting at the Laravel level:

```php
// routes/web.php — add middleware
Route::middleware('throttle:60,1')->group(function () {
    // routes here
});
```

### Database Exposure

Your MySQL database running on `127.0.0.1:3306` is **not exposed** by the tunnel. The tunnel only forwards HTTP traffic to your local server. Your database remains local.

---

## 8. Troubleshooting

### Common Issues

| Issue | Solution |
|---|---|
| **Assets not loading (404)** | Run `npm run build` to compile assets. The tunnel serves from `public/`. |
| **Session not persisting** | Set `SESSION_SECURE_COOKIE=true` in `.env`. |
| **Mixed content warnings** | Ensure `APP_URL` uses `https://`, not `http://`. |
| **Redirect loops** | Check that `SESSION_DOMAIN` is not set to a domain that doesn't match the tunnel URL. |
| **CORS errors** | Add tunnel domain to `config/cors.php` `allowed_origins`. |
| **"Host is not trusted"** | Add tunnel domain to trusted hosts in `AppServiceProvider`. |

### Verify Tunnel is Working

```bash
# Test the tunnel endpoint
curl -I https://your-tunnel.trycloudflare.com

# Check Laravel logs for errors
tail -f storage/logs/laravel.log
```

---

## 9. Quick Reference Commands

```bash
# Start quick tunnel
cloudflared tunnel --url http://localhost:8000

# Start named tunnel
cloudflared tunnel run panteon-dev

# Stop tunnel
Ctrl+C

# Check tunnel status
cloudflared tunnel info panteon-dev

# List all tunnels
cloudflared tunnel list
```

---

## 10. Summary Checklist

- [ ] Install `cloudflared` on your system
- [ ] Update `APP_URL` in `.env` to the tunnel HTTPS URL
- [ ] Set `APP_DEBUG=false` if testing with external users
- [ ] Set `SESSION_SECURE_COOKIE=true` in `.env`
- [ ] Run `php artisan config:clear` after env changes
- [ ] Run `npm run build` if assets aren't loading
- [ ] Update webhook URLs in third-party services
- [ ] Add tunnel domain to CORS allowed origins if needed
- [ ] Verify the tunnel URL loads correctly in a browser
- [ ] Test login/session functionality works over the tunnel
