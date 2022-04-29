 WebCalendar Developer Guide 

WebCalendar Developer Guide
===========================

Table of Contents
-----------------

*   [Introduction](#intro)
*   [System Requirements](#requirements)
*   [Getting The Code](#getcode)
*   [Setting Up Your Dev Environment](#dev)
*   [Naming Conventions](#conventions)
*   [Coding Standards](#standards)
*   [Submitting Changes](#patch)
*   [Translations and Languages](#translations)
*   [FAQ](#faq)
*   [Resources](#resources)

* * *

# Introduction

WebCalendar is written in PHP. A minimum of PHP 7.3 is required to run WebCalendar due to the use of classes and sessions.
PHP 8 is recommended.

# Getting The Code

You should always be using the latest code from git.  You can start with the 'master'
branch (which should be the code used in the latest public release).
Or, you can start with the 'develop' branch which is where new changes are staged
before the next public release.

[https://github.com/craigk5n/webcalendar](https://github.com/craigk5n/webcalendar)

To obtain the code from your command line using the git command:

`git clone https://github.com/craigk5n/webcalendar.git`

# Setting Up Your Dev Environment

You can either run WebCalendar locally if you already have a web server with PHP
support and supported database installed (MariaDb, etc.)
That is pretty easy to do for both Linux and Mac.
You can also run WebCalendar inside a Docker container.

## Setting Up a Docker Dev Environment (PHP 8.1)

You can setup a docker environment with PHP 8.1 and MariaDb with a few
steps.  This docker setup differs from the normal WebCalendar docker image
in that the local WebCalendar files are mounted into the container so
that changes to your local filesystem will also apply to the WebCalanedar
files in the container.
You will need to have cloned the repo to start.

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
  GRANT ALL PRIVILEGES ON *.* TO webcalendar_php8@localhost IDENTIFIED BY 'Webcalendar.1'  WITH GRANT OPTION; 
  FLUSH PRIVILEGES;
  QUIT
  ```
- Start up your web browser and go to:
  [http://localhost:8080/](http://localhost:8080/).
- Follow the guided web-based setup and choose "mysqli" as the database
  type.
  Be sure to use the same MariaDb credentials specified above
  (Password _WebCalendar.1_ and Database Name _webcalendar_php8_.)

## Setting Up a Docker Dev Environment (PHP 7.4)

You can setup a docker environment with PHP 7.4 and MariaDb with a few
steps.
You will need to have cloned the repo to start.

- Build the docker container with `docker-compose -f docker-compose-php7.yml build`
- Start the containers with `docker-compose -f docker-compose-php7.yml up`
- In order to grant the proper permissions inside of MariaDb, you
  will need to run a few MySQL commands.  First shell into the mariadb
  container: `docker-compose exec db /bin/sh`
- Start up the db client: `/bin/mariadb -p` (the password will be
  "Webcalendar.1" as specified in the `docker-compose-php7.yml' file.  You
  can change it to make your dev environment more secure (before you
  build the containers in step above).
- Run the following db commands:
  ```
  GRANT ALL PRIVILEGES ON *.* TO webcalendar@localhost IDENTIFIED BY 'Webcalendar.1'  WITH GRANT OPTION; 
  FLUSH PRIVILEGES;
  QUIT
  ```
- Start up your web browser and go to:
  [http://localhost:8080/](http://localhost:8080/).
- Follow the guided web-based setup and choose "mysqli" as the database
  type.
  Be sure to use the same MariaDb credentials specified above
  (Password _WebCalendar.1_ and Database Name _webcalendar_.)

# Naming Conventions

I would really like to say WebCalendar uses standard naming conventions.
But that's not the case.  Code has been contributed by many different developers.
So, just try to be consistent with what you see in the existing code.

## Database Table Names

Database table names should be prefixed with 'webcal\_'. Names should be in lowercase with words\_separated\_by\_underscores. Examples:

*   webcal\_user\_pref
*   webcal\_entry

## Preference Value Names

These are variables stored in webcal\_config and webcal\_user\_pref tables. Names should be in uppercase with words\_separated\_by\_underscores. Examples:

*   ALLOW\_HTML\_DESCRIPTION
*   DISABLE\_ACCESS\_FIELD

# Coding Standards

## Indenting

In general, 2 spaces are used for indentation.  However, there are many counter examples.
Try to stick to this guideline when possible.
Also, avoid lines that are way beyond 100 characters if it can be easily avoided.

## File Format

Unix format only (LF ASCII 0x0A), no Windows or Mac format files.

## PHP function comments

It would be great if all functions were documented using standard 
[phpDocumentor](http://www.phpdoc.org/) format.  We're not generating
documentation based on it, but it does show in most IDEs.

## HTML5

We used to use XHTML, but all pages should now be [HTML5](https://www.w3.org/TR/2014/REC-html5-20141028/introduction.html).

# Submitting Changes

Please use Github's [pull request](https://github.com/craigk5n/webcalendar/pulls)
feature to contribute changes to WebCalendar.

# Translations and Languages

When adding or modifying WebCalendar code, all displayed text should be translatable.
The following tips will ensure new text can be translated quickly and efficiently.

## Translate

All displayable text should be sent to the _translate ()_ function, which returns the text translated in the user's language of choice. A variation of this function is _etranslate ()_, which includes and echo command. When translating text within javascript, always set the _decode_ flag to true. This will allow proper decoding of htmlentities.

## Htmlentities

When used, this function tag should include the current charset when displaying database results. This will be most important when dealing with languages such as Japanese that tend to contain characters that would otherwise be non-displayable. Although this is not the perfect solution, it seems to suffice for our purposes. Possibly, a better technique would be to use the charset of the original creator of the data, but this is beyond current capabilities.  
For reference see: [http://us3.php.net/manual/en/function.htmlentities.php](http://us3.php.net/manual/en/function.htmlentities.php)

## Updating Language Files

When text is added or updated, requiring new translations, the translations/English-US.txt file should be updated as a minimum. This file will be used as the basis for updating the other language files and needs to be up to date. From within the tools directory, the following command will search through the WebCalendar source files and update the language file specified. Language files should always be committed to CVS in Unix format to save space.

`perl update_translation.pl English-US.txt`

## Frequently Asked Questions

### Why aren't you using [DBA](https://www.php.net/manual/en/book.dba.php),
[ADODB](https://adodb.org/dokuwiki/doku.php) or some other database
abstraction layer for database access?

WebCalendar started before most of these.  I would not be opposed to switching to
something this will likely be maintained so long as PHP is.
For now, I'm partial to my fairly lean dbi4php.php solution.

### Why aren't you using the PEAR database functions?

WebCalendar pre-dates the PEAR database functions. There does not seem to be sufficient reason to switch from our existing code at this point.

### Why aren't you using a template engine like smarty?

WebCalendar pre-dates most of the template engines out there.

If someone wants to tackle converting all of WebCalendar to one of better template engines
and submits it as a PR, I wouldn't complain...

# Resources

The following resources may be helpful:

*   [Additional Developer Resources](https://www.k5n.us/k5ncal/k5ncal-developer-resources/):
    This is the best place to start
*   [Discussions](https://github.com/craigk5n/webcalendar/discussions) on Github is a good place to ask questions
*   [Issues](https://github.com/craigk5n/webcalendar/issues) on Github is where you can report bugs,
    suggest new features, or find an issue you'd like to fix
*   [Pull Requests](https://github.com/craigk5n/webcalendar/pulls) on Github
*   [WebCalendar Function Documentation](WebCalendar-Functions.html)
*   [WebCalendar Database Schema](https://www.k5n.us/webcalendar/webcalendar-database-schema/) describes the WebCalendar database schema

