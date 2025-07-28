#!/bin/bash
source env.sh

docker build -t MultiverseIdle:local .

docker run -v $PWD:/app -p 8383:80 -p 4444:443 -p 443:443/udp --tty -it --rm \
--name MultiverseIdle-Local MultiverseIdle:local
