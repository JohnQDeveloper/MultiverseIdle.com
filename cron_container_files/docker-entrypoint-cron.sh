#!/bin/bash
set -e

echo "=========================================="
echo "MultiverseIdle Cron Container Starting"
echo "=========================================="
echo "Environment:"
echo "  DB_HOST: ${DB_HOST}"
echo "  REDIS_HOST: ${REDIS_HOST}:${REDIS_PORT}"
echo "=========================================="

# Create log file
touch /var/log/cron.log

# Export environment variables to a file that cron can use
printenv | grep -v "no_proxy" | sed 's/^\(.*\)$/export \1/g' > /etc/environment-vars.sh
chmod +x /etc/environment-vars.sh

# Update crontab to source environment variables
echo "* * * * * . /etc/environment-vars.sh && cd /app/crons && /usr/local/bin/php run_all.php >> /var/log/cron.log 2>&1" | crontab -

# Start cron in foreground
echo "Starting cron daemon..."
cron

# Tail the log file to keep container running and show output
echo "Cron daemon started. Tailing logs..."
echo "=========================================="
tail -f /var/log/cron.log
