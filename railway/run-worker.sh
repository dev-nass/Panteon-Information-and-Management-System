#!/bin/bash
# Make sure this file has executable permissions, run `chmod +x railway/run-worker.sh`

#!/bin/bash
until php artisan db:show > /dev/null 2>&1; do
  echo "Waiting for database..."
  sleep 2
done

while true; do
  php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --queue=default --verbose
  echo "Worker exited (code $?), restarting in 2s..."
  sleep 2
done