#!/bin/sh

set -eu

. docker/.prepare.sh

command=$@

if [ "$command" = "" ]; then
    command="bash -l"
    >&2 echo
    >&2 echo "Welcome to WordPress testing shell!"
    >&2 echo "Your plugin is mounted to /app and all composer dependencies are put to PATH."
    >&2 echo
    >&2 echo "Try: codecept run wpunit"
    >&2 echo " or: codecept run functional"
    >&2 echo
fi

exec docker exec -it "${WPTT_CONTAINER_NAME}-wp" $command