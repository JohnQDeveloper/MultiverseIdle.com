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

# Start cron in foreground
echo "Starting cron daemon..."
cron

# Tail the log file to keep container running and show output
echo "Cron daemon started. Tailing logs..."
echo "=========================================="
tail -f /var/log/cron.log
