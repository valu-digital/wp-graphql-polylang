#!/bin/sh

set -eu

if [ ! -f docker/compose.sh ]; then
    >&2 echo
    >&2 echo "Oops! The docker scripts are supposed to be run from the parent directory"
    >&2 echo "Ex. ./docker/compose.sh"
    >&2 echo
    exit 1
fi

. docker/.prepare.sh

command=$@

if [ "$command" = "" ]; then
    command="up --abort-on-container-exit --build"
fi

exec docker-compose -f docker/docker-compose.yml $command