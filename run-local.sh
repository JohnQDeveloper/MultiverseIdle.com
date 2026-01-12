#!/bin/bash
source env.sh

docker build -t multiverse-idle:local \
.

echo "RUNNING VALKEY\n"
docker run --rm -d -p 6379:6379 valkey/valkey:8.1.3
echo "VALKEY IS RUNNING\n"

docker run -v $PWD:/app -p 80:80 -p 443:443 --tty -it --rm \
--env DB_USER=$DB_USER \
--env DB_PASSWORD=$DB_PASSWORD \
--env DB_HOST=$DB_HOST \
--env RESEND_API_KEY=$RESEND_API_KEY \
--env REDIS_HOST=$REDIS_HOST \
--env REDIS_PORT=$REDIS_PORT \
--env DEBUG=true \
--env ENVIRONMENT=Dev \
--env HOSTNAME=localhost \
--name multiverse-idle-local multiverse-idle:local
