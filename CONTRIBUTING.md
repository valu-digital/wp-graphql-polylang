# Contributing

This project uses [WP Testing Tools][] to run tests with Codeception and
wp-browser.

To run the tests all you need is git and Docker. On Windows you should run
the commands from git-bash.

In the git repository root run

    docker/compose.sh

This will build and start up the Docker environment. It will take awhile but
only on the first time.

Once it's running on a second terminal run to open the testing shell

    docker/shell.sh

and in there run

    composer test

For more information checkout the [WP Testing Tools][] README.

[wp testing tools]: https://github.com/valu-digital/wp-testing-tools
