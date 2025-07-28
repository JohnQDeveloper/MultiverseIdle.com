#!/bin/bash
source env.sh
docker build -t MultiverseIdle:local .
docker run -it --rm --name MultiverseIdle-Local MultiverseIdle:local
