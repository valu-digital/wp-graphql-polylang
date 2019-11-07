# Running the tests

Here's how to run the tests on Ubuntu Bionic

Install php, mariadb and some dev tools

    sudo apt install php-cli php-curl php-gd php-mbstring php-zip php-dom php-mysql subversion git zip mariadb-server mariadb-client

Add testadmin user for mariadb

    sudo mysql
    CREATE USER 'testadmin'@'localhost' IDENTIFIED BY 'password';
    GRANT ALL PRIVILEGES ON *.* TO 'testadmin'@'localhost' WITH GRANT OPTION
    FLUSH PRIVILEGES

Get Composer

    sudo wget https://getcomposer.org/download/1.9.1/composer.phar -O /usr/local/bin/composer
    sudo chmod a+x /usr/local/bin/composer

Clone the repostory

    git clone https://github.com/valu-digital/wp-graphql-polylang.git
    cd wp-graphql-polylang

Copy .env file

    cp .env.local .env

Install testing deps

    composer install

Install WordPress testing tools

    composer install-wp-tests

And finally run the tests

    composer test
