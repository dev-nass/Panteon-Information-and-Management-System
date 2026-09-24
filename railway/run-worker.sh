#!/bin/bash
# Make sure this file has executable permissions, run `chmod +x railway/run-worker.sh`

# This command runs the queue worker (HTTPS-based Resend mailer works on Railway where SMTP 587 is blocked).
# --sleep=3 avoids hot-loop, --tries=3 matches config/queue.php retry_after 90, --max-time=3600 recycles worker hourly.
php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --queue=default --verbose