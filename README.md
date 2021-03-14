WebCalendar README
------------------

Project Home Page: http://k5n.us/wp/webcalendar/
Project Owner: Craig Knudsen, &#99;&#114;&#97;&#105;&#103;&#64;&#107;&#53;&#110;&#46;&#117;&#115;  
Documentation:
- [System Administrator's Guide](http://htmlpreview.github.io/?https://github.com/craigk5n/webcalendar/blob/master/docs/WebCalendar-SysAdmin.html) (Installation instructions, FAQ)
- [Upgrading Instructions](http://htmlpreview.github.io/?https://github.com/craigk5n/webcalendar/blob/master/UPGRADING.html)
- License: [GPLv2](https://github.com/craigk5n/webcalendar/blob/master/LICENSE)
- [Online Demo at SF](http://webcalendar.sourceforge.net/demo/)

Developer Resources:
- [WebCalendar-Database.html](http://htmlpreview.github.io/?https://github.com/craigk5n/webcalendar/blob/master/docs/WebCalendar-Database.html)
- [WebCalendar-DeveloperGuide.html](http://htmlpreview.github.io/?https://github.com/craigk5n/webcalendar/blob/master/docs/WebCalendar-DeveloperGuide.html)

## Setting up a docker dev environment

You can setup a docker environment with PHP 7.4 and MariaDb with a few
steps.

- Build the docker container with `docker-compose build`
- Start the containers with `docker-compose up`
- In order to grant the proper permissions inside of MariaDb, you
  will need to run a few MySQL commands.  First shell into the mariadb
  container: `docker-compose exec db /bin/sh`
- Start up the db client: `/bin/mariadb -p` (the password will be
  "Webcalendar.1" as specified in the `docker-compose.yml' file.  You
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


