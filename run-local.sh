#!/bin/bash
source env.sh

sudo docker build -t multiverse-idle:local .

sudo docker run -v $PWD:/app -p 8383:80 -p 4444:443 -p 443:443/udp --tty -it --rm \
--name multiverse-idle-local multiverse-idle:local
