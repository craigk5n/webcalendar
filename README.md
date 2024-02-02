WebCalendar README
------------------

Project Home Page: https://k5n.us/webcalendar/
Project Owner: Craig Knudsen, &#99;&#114;&#97;&#105;&#103;&#64;&#107;&#53;&#110;&#46;&#117;&#115;
Documentation:
- [System Administrator's Guide](http://htmlpreview.github.io/?https://github.com/craigk5n/webcalendar/blob/master/docs/WebCalendar-SysAdmin.html) (Installation instructions, FAQ)
- [Upgrading Instructions](http://htmlpreview.github.io/?https://github.com/craigk5n/webcalendar/blob/master/UPGRADING.html)
- License: [GPLv2](https://github.com/craigk5n/webcalendar/blob/master/LICENSE)
- [Online Demo at SF](http://webcalendar.sourceforge.net/demo/)

Developer Resources:
- [WebCalendar-Database.html](http://htmlpreview.github.io/?https://github.com/craigk5n/webcalendar/blob/master/docs/WebCalendar-Database.html)
- [WebCalendar-DeveloperGuide.html](http://htmlpreview.github.io/?https://github.com/craigk5n/webcalendar/blob/master/docs/WebCalendar-DeveloperGuide.html)

## Roadmap

### v1.9.X
- Clean up bugs
- Resolve any PHP 8 issues
- New installer
- Better support for running as a container
- Improve translations for most popular languages (using new tools/complete-translation.py.)
- Testing/validation of databases other than MySQL (which has received most of the attention)

### v2.0.X
- Stable release based on the v1.9 releases

### v2.1.X
- Add support for database caching with Redis

## Installation Instructions

After unzipping your files (or transferring the files to your hosting
provider), you will need to go to the web-based install script.
If your files are installed in a "webcalendar" folder under your parent
web server document root, you can access the script by going to:

    https://yourserverhere/webcalendar/

(Obviously, put the correct server name in above.)  The toplevel URL will
automatically redirect to the installation wizard.

Alternatively, there is a headless installation/update script you can run from
the shell:

```shell
php webcalendar/install/headless.php
```

### Option 1: settings.php

You should create an empty `includes/settings.php` yourself before running the headless
install script that the web service process has write permissions to.

### Option 2: Environment Variables

Instead of using the `includes/settings.php` file for your settings, you can use environment
variables instead. 
This is primarily targeted towards those running WebCalendar in containers (docker, AWS Fargate, etc)
where it's easier to pass in environment settings than to write to a config file on a container. 
You can do this with the docker-compose file if you are using docker.
If you want to do this with a standard Web Server,
you can set the evironment variables within your web server's configuration. 
Depending on your web server, there may be more than one way to do this.  You could
do this in your `php.ini` file, but those env vars would be available to all PHP apps
on the server.  A better solution would be to setup a `.htaccess` file in your
WebCalendar directory that looked something like:

```
SetEnv WEBCALENDAR_USE_ENV true
SetEnv WEBCALENDAR_INSTALL_PASSWORD "da1437a2c74ee0b35eed71e27d00c618"
SetEnv WEBCALENDAR_DB_TYPE mysqli
SetEnv WEBCALENDAR_DB_DATABASE webcalendar
SetEnv WEBCALENDAR_DB_LOGIN webcalendar
SetEnv WEBCALENDAR_DB_PASSWORD "ChangeThisPassword"
SetEnv WEBCALENDAR_DB_HOST db
SetEnv WEBCALENDAR_DB_PERSISTENT true
SetEnv WEBCALENDAR_USER_INC user.php
SetEnv WEBCALENDAR_MODE prod
```

Note: Don't forget to enable mod_env for Apache for this to work, and to allow access to environment variables from PHP:
```
php_value expose_php ON
```

## Running WebCalendar with Docker
You can use a prebuilt WebCalendar image rather than building it yourself locally.
You will need to shell into the MariaDb container to grant access.
Because we also need a database, we use a local network with WebCalendar
and MariaDb running that is setup with the `docker-compose` command.

- Start the containers:
  `docker-compose -f docker/docker-compose-php8.yml up`
- In order to grant the proper permissions inside of MariaDb, you
  will need to run a few MySQL commands.  First shell into the mariadb
  container: `docker-compose -f docker/docker-compose-php8.yml exec db /bin/sh`
- Start up the db client: `/bin/mariadb -p` (the password will be
  "Webcalendar.1" as specified in the `docker-compose-php8.yml' file.  You
  can change it to make your dev environment more secure (before you
  build the containers in step above).
- Run the following db commands:
  ```
  GRANT ALL PRIVILEGES ON *.* TO webcalendar_php8@localhost IDENTIFIED BY 'Webcalendar.1' WITH GRANT OPTION;
  FLUSH PRIVILEGES;
  QUIT
  ```
- Start up your web browser and go to:
  [http://localhost:8080/](http://localhost:8080/).
- Follow the guided web-based setup and choose "mysqli" as the database
  type.
  Be sure to use the same MariaDb credentials specified above
  (Password _WebCalendar.1_ and Database Name _webcalendar_php8_.)

## Setting Up a Docker Dev Environment (PHP 8.1)

You can setup a docker environment with PHP 8.1 and MariaDb with a few
steps.  This docker setup differs from the normal WebCalendar docker image
in that the local WebCalendar files are mounted into the container so
that changes to your local filesystem will also apply to the WebCalanedar
files in the container.

- Build the docker container with
  `docker-compose -f docker/docker-compose-php8-dev.yml build`
- Start the containers with
  `docker-compose -f docker/docker-compose-php8-dev.yml up`
- In order to grant the proper permissions inside of MariaDb, you
  will need to run a few MySQL commands.  First shell into the mariadb
  container: `docker-compose -f docker/docker-compose-php8-dev.yml exec db /bin/sh`
- Start up the db client: `/bin/mariadb -p` (the password will be
  "Webcalendar.1" as specified in the `docker-compose-php8-dev.yml' file.  You
  can change it to make your dev environment more secure (before you
  build the containers in step above).
- Run the following db commands:
  ```
  GRANT ALL PRIVILEGES ON *.* TO webcalendar_php8@localhost IDENTIFIED BY 'Webcalendar.1' WITH GRANT OPTION;
  FLUSH PRIVILEGES;
  QUIT
  ```
- Start up your web browser and go to:
  [http://localhost:8080/](http://localhost:8080/).
- Follow the guided web-based setup and choose "mysqli" as the database
  type.
  Be sure to use the same MariaDb credentials specified above
  (Password _WebCalendar.1_ and Database Name _webcalendar_php8_.)

## Integrating WebCalendar with External Applications

Web Calendar can be configured to pull user and configuration data from an external application. This
allows tighter integration when using Web Calendar alongside your own website or application.

The user integration is accomplished by creating a "bridge" script in the `includes` directory, for example,
`includes/user-app-myapp.php`. There are several functions you will need to define in this script. See the
built-in integrations for [Joomla](https://github.com/craigk5n/webcalendar/blob/master/includes/user-app-joomla.php)
and [LDAP](https://github.com/craigk5n/webcalendar/blob/master/includes/user-ldap.php) as examples for the interface
you'll need to implement.

Once the script is created, add the following line to `includes/settings.php`:

```
user_inc: user-app-myapp.php
```

The process is much the same for external configs. Create a script such as `includes/config-app-myapp.php` and define
a single function in that script called `do_external_configs`. This function receives an associative array of all
the settings defined in `includes/settings.php`, and should return a new associated array that overrides those settings.
Then, simply add this line to `includes/settings.php`:

```
config_inc: config-app-myapp.php
```

External configs will allow your application to supply, for example, database credentials to Web Calendar, rather than
these needing to be stored in plain text in the webcalendar directory.
