#!/bin/sh

set -eu

if [ "${WP_DOCKER:-}" != "1" ]; then
    >&2 echo "This script is for the Docker container init."
    exit 1
fi

# Keep the container running so it can be accessed with the shell
keepalive() {
    exec tail -f /dev/null
}

if [ "${WPTT_NO_INIT:-}" = "1" ]; then
    >&2 echo "Container running ok. Enter it with 'docker/run shell'"
    keepalive
fi

composer install

composer wp-install


if [ "$(wp-install --status)" = "full" ]; then
    >&2 echo "WP is running at http://localhost:8080/ and run tests against it using 'docker/run shell'"
    exec wp-install --serve
else
    # Otherwise just keep the container running so it can be accessed with docker/shell.sh
    >&2 echo "Container running ok. Enter it with 'docker/run shell'"
    keepalive
fi
