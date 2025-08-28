#!/bin/bash
source env.sh

docker build -t multiverse-idle:local \
--build-arg DB_USER=$DB_USER \
--build-arg DB_PASSWORD=$DB_PASSWORD \
--build-arg DB_HOST=$DB_HOST \
--build-arg RESEND_API_KEY=$RESEND_API_KEY \
.

docker run -v $PWD:/app -p 3456:80 -p 4444:443 --tty -it --rm \
--name multiverse-idle-local multiverse-idle:local
