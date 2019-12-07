#!/bin/sh

set -eu

if [ "${WP_DOCKER:-}" != "1" ]; then
    >&2 echo "This script is for the Docker container init."
    exit 1
fi

composer install

composer wp-install

if [ -f init-docker.sh ]; then
    # Run the custom init if any
    exec ./init-docker.sh
fi

if [ "$(wp-install --status)" = "full" ]; then
    >&2 echo "WP installed. You can access it from http://localhost:8080/ and run tests against it using ./docker/shell.sh"
    exec wp-install --serve
else
    # Otherwise just keep the container running so it can be accessed with docker/shell.sh
    >&2 echo "Init ok! Start the shell in antoher terminal with ./docker/shell.sh"
    exec tail -f /dev/null
fi
