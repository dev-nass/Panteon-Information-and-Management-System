#!/bin/bash
# Make sure this file has executable permissions, run `chmod +x railway/run-worker.sh`

# wait for DB (max ~60s so it can never hang forever)
for i in {1..30}; do
  php artisan migrate:status > /dev/null 2>&1 && break
  echo "Waiting for database... ($i/30)"
  sleep 2
done

while true; do
  php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --queue=default --verbose
  echo "Worker exited (code $?), restarting in 2s..."
  sleep 2
done