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

# Export app environment variables to a file that cron can source
cat > /etc/environment-vars.sh <<EOF
export DB_USER="${DB_USER}"
export DB_PASSWORD="${DB_PASSWORD}"
export DB_HOST="${DB_HOST}"
export RESEND_API_KEY="${RESEND_API_KEY}"
export REDIS_HOST="${REDIS_HOST}"
export REDIS_PORT="${REDIS_PORT}"
export DEBUG="${DEBUG}"
export ENVIRONMENT="${ENVIRONMENT}"
export HOSTNAME="${HOSTNAME}"
EOF
chmod +x /etc/environment-vars.sh

# Update crontab to source environment variables and output to log file
# Add timestamp to each cron run for easier debugging
echo "* * * * * . /etc/environment-vars.sh && { echo \"[\\$(date '+\%Y-\%m-\%d \%H:\%M:\%S')] Starting cron run...\"; cd /app/crons && /usr/local/bin/php -d display_errors=1 -d error_reporting=E_ALL run_all.php; echo \"[\\$(date '+\%Y-\%m-\%d \%H:\%M:\%S')] Cron run completed.\"; } >> /var/log/cron.log 2>&1" | crontab -

# Show loaded crontab for debugging
echo "Loaded crontab:"
crontab -l
echo "=========================================="

# Test PHP execution and configuration before starting cron
echo "Testing PHP configuration and connectivity..."
. /etc/environment-vars.sh
php -d display_errors=1 -d error_reporting=E_ALL -r "
    require_once('/app/config.php');
    echo 'PHP: OK' . PHP_EOL;
    echo 'Database: ' . (isset(\$db) ? 'Connected' : 'Failed') . PHP_EOL;
    echo 'Redis: ' . (isset(\$redis) && \$redis->ping() ? 'Connected' : 'Failed') . PHP_EOL;
    echo 'Debug Mode: ' . (DEBUG ? 'Enabled' : 'Disabled') . PHP_EOL;
    echo 'Environment: ' . ENVIRONMENT . PHP_EOL;
" || echo "WARNING: Connectivity test failed (services may not be ready yet). Cron will retry on each run."
echo "=========================================="

# Start cron daemon
echo "Starting cron daemon..."
echo "Cron jobs will execute every minute..."
echo "=========================================="

# Start cron in the background, then tail the log to keep the container alive
cron
exec tail -f /var/log/cron.log
