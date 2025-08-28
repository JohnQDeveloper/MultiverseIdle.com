#!/bin/bash
source env.sh

docker build -t multiverse-idle:local \
--build-arg DB_USER=$DB_USER \
--build-arg DB_PASSWORD=$DB_PASSWORD \
--build-arg DB_HOST=$DB_HOST \
--build-arg RESEND_API_KEY=$RESEND_API_KEY \
--build-arg REDIS_HOST=$REDIS_HOST \
--build-arg REDIS_PORT=$REDIS_PORT \
.

echo "RUNNING VALKEY\n"
docker run --rm -d -p 6379:6379 valkey/valkey:8.1.3
echo "VALKEY IS RUNNING\n"

docker run -v $PWD:/app -p 3456:80 -p 4444:443 --tty -it --rm \
--name multiverse-idle-local multiverse-idle:local
