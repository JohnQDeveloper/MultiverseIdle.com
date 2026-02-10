#!/bin/bash
source env.sh

echo "REMOVING EXSISTING CONTAINERS...\n" && \
docker stop valkey && docker rm -f valkey 2>/dev/null || true && \
docker stop multiverse-idle-web && docker rm -f multiverse-idle-web 2>/dev/null || true && \
docker stop multiverse-idle-cron && docker rm -f multiverse-idle-cron 2>/dev/null || true && \
echo "REMOVED EXISTING CONTAINERS\n"

echo "CREATING DOCKER NETWORK...\n" && \
docker network create multiverse-idle-network 2>/dev/null || true && \
echo "NETWORK CREATED\n"

echo "BUILDING CONTAINERS...\n" && \
echo "BUILDING WEB CONTAINER...\n" && \
docker build -t multiverse-idle-web:local -f Dockerfile.web.dev .  && \
echo "\tWEB CONTAINER BUILT\n"  && \
echo "\tBUILDING CRON CONTAINER\n" && \
docker build -t multiverse-idle-cron:local -f Dockerfile.cron.dev .  && \
echo "\tCRON CONTAINER BUILT\n"  && \

echo "LAUNCHING CONTAINERS...\n" && \
echo "\tRUNNING VALKEY\n"  && \

docker run --rm -d -p 6379:6379 --network multiverse-idle-network --name valkey valkey/valkey:8.1.3  && \
echo "\tVALKEY IS RUNNING\n" && \
echo "\tRUNNING WEB CONTAINER\n"  && \
docker run --rm -d -v $PWD:/app -p 80:80 -p 443:443 --network multiverse-idle-network --tty -it \
--env DB_USER=$DB_USER \
--env DB_PASSWORD=$DB_PASSWORD \
--env DB_HOST=$DB_HOST \
--env RESEND_API_KEY=$RESEND_API_KEY \
--env REDIS_HOST=valkey \
--env REDIS_PORT=6379 \
--env DEBUG=true \
--env ENVIRONMENT=Dev \
--env HOSTNAME=localhost \
--name multiverse-idle-web multiverse-idle-web:local
echo "\tWEB CONTAINER IS RUNNING\n" && \
echo "\tRUNNING CRON CONTAINER\n"  && \
docker run --rm -d -v $PWD:/app --network multiverse-idle-network --tty -it \
--env DB_USER=$DB_USER \
--env DB_PASSWORD=$DB_PASSWORD \
--env DB_HOST=$DB_HOST \
--env RESEND_API_KEY=$RESEND_API_KEY \
--env REDIS_HOST=valkey \
--env REDIS_PORT=6379 \
--env DEBUG=true \
--env ENVIRONMENT=Dev \
--env HOSTNAME=localhost \
--name multiverse-idle-cron multiverse-idle-cron:local
echo "\tCRON CONTAINER IS RUNNING\n" && \
echo "CONTAINERS LAUNCHED SUCCESSFULLY\n" && \
echo "ACCESS THE WEB APP AT http://localhost\n" && \
echo "TO VIEW LOGS, USE 'docker logs <container_name>'\n" && \
echo "TO RESTART CONTAINERS, RE-RUN THIS SCRIPT \n"
