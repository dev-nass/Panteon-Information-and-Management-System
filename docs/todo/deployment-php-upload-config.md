# Deployment & PHP Upload Size Configuration

## Context

The Certificate of Service template upload feature accepts PDF files up to 10MB. PHP's default `upload_max_filesize` is **2M** and `post_max_size` is **8M**, which will reject any file larger than 2MB before Laravel even processes the request. This causes a generic "The file failed to upload." error with no useful detail. These limits must be increased on every environment (local, staging, production) that runs this application.

---

## Required PHP Configuration

| Setting | Default | Required | Purpose |
|---|---|---|---|
| `upload_max_filesize` | 2M | **40M** | Max size of a single uploaded file |
| `post_max_size` | 8M | **40M** | Max size of the entire POST body (must be >= upload_max_filesize) |

Both must be set. If `post_max_size` is smaller than `upload_max_filesize`, the upload will still fail silently.

---

## How to Apply

### 1. Find your php.ini

Run this on the target server:

```bash
php -i | grep "Loaded Configuration File"
```

Common paths:

| Environment | Path |
|---|---|
| **Ubuntu/Debian CLI** | `/etc/php/8.3/cli/php.ini` |
| **Ubuntu/Debian Apache** | `/etc/php/8.3/apache2/php.ini` |
| **Ubuntu/Debian Nginx (FPM)** | `/etc/php/8.3/fpm/php.ini` |
| **CentOS/RHEL** | `/etc/php.ini` |
| **XAMPP (Linux)** | `/opt/lampp/etc/php.ini` |
| **XAMPP (Windows)** | `C:\xampp\php\php.ini` |
| **Hostinger (shared)** | `/opt/alt/php83/etc/php.ini` |
| **Docker (official image)** | `/usr/local/etc/php/php.ini` or custom `.ini` file |

> **Important:** CLI and web server PHP have separate `php.ini` files. Update both if they differ.

### 2. Edit php.ini

```bash
sudo nano /etc/php/8.3/apache2/php.ini
```

Search for these lines and change them:

```ini
upload_max_filesize = 40M
post_max_size = 40M
```

### 3. Restart the web server

| Server | Command |
|---|---|
| **Apache** | `sudo systemctl restart apache2` |
| **Nginx + PHP-FPM** | `sudo systemctl restart php8.3-fpm` |
| **XAMPP** | `sudo /opt/lampp/lampp restart` |
| **Docker** | Rebuild the container |

### 4. Verify

```bash
# CLI check
php -i | grep -E "upload_max_filesize|post_max_size"

# Web check — create a temporary PHP file in public/:
# <?php phpinfo(); ?>
# Visit it in the browser, search for "upload_max_filesize"
# Delete the file after checking
```

---

## Alternative Methods (No php.ini Access)

### Via .htaccess (Apache only)

Add to the project's `public/.htaccess`:

```apache
php_value upload_max_filesize 40M
php_value post_max_size 40M
```

> Does not work on Nginx. Only applies to Apache with `mod_php`.

### Via .user.ini (PHP-FPM / shared hosting)

Create a `.user.ini` file in the project root:

```ini
upload_max_filesize = 40M
post_max_size = 40M
```

> Some shared hosts ignore this. Check with your provider.

### Via Laravel Middleware (no server config access)

If you cannot change PHP config at all, you can reject oversized files before PHP processes them by adding a middleware check. However, this is a workaround — the proper fix is updating php.ini.

---

## Environment-Specific Notes

### Laravel Cloud

Laravel Cloud allows up to 50MB uploads by default. No configuration needed.

### Laravel Forge / VPS

SSH into the server, edit the php.ini for your Nginx/Apache site, and restart the service. Forge provides a PHP config editor in the dashboard for some settings.

### Hostinger (Shared Hosting)

- hPanel → **PHP Configuration** → select PHP version → switch to **Options** tab
- If the setting is not editable there, contact support or use `.user.ini`
- Hostinger VPS: full SSH access, edit php.ini directly

### Docker

Add to your `Dockerfile`:

```dockerfile
RUN sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 40M/' /usr/local/etc/php/php.ini \
    && sed -i 's/post_max_size = 8M/post_max_size = 40M/' /usr/local/etc/php/php.ini
```

Or mount a custom `.ini` file:

```yaml
# docker-compose.yml
services:
  app:
    volumes:
      - ./docker/php/upload.ini:/usr/local/etc/php/conf.d/upload.ini
```

```ini
; docker/php/upload.ini
upload_max_filesize = 40M
post_max_size = 40M
```

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| **"The file failed to upload."** (generic) | PHP rejected the file before Laravel processed it | Check `php -i` — limits are likely still at 2M |
| **Upload works for small files but not large ones** | `upload_max_filesize` too low | Increase to 40M |
| **File uploads but is truncated or empty** | `post_max_size` smaller than file size | Increase `post_max_size` to match or exceed `upload_max_filesize` |
| **Error shows just "T"** | Frontend error handler was indexing a string instead of array | Fixed in `IndexView.vue` — now handles both types |
| **Error shows but no file in storage** | Missing `storage/app/certificate_templates/` directory | Controller now auto-creates it; also run `mkdir -p storage/app/certificate_templates` |
| **CLI shows correct limits but upload still fails** | Web server uses a different php.ini | Check the web server's loaded config via `phpinfo()` |

---

## Verification Checklist

After deploying to any environment:

- [ ] `php -i | grep upload_max_filesize` shows `40M`
- [ ] `php -i | grep post_max_size` shows `40M`
- [ ] Restarted the web server (Apache/Nginx/PHP-FPM)
- [ ] `storage/app/certificate_templates/` directory exists and is writable
- [ ] Uploaded a test PDF (>2MB) on the Certificate Templates page
- [ ] Preview button works on uploaded templates
- [ ] If using Docker, the `.ini` file is mounted or baked into the image
