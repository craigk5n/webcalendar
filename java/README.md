# WebCalendar Web Services & Java Client - version 0.2

Note: The web services files are not a standard part of WebCalendar
  and can be found in the "ws" directory or your WebCalendar install.

WebCalendar Web Services:
- Propietary Web Services written in PHP (that are not SOAP or
  XML-RPC)
  + login.php
  + get_reminders.php
  + get_events.php
  + more to come soon... ?
- A Java library for accessing WebCalendar functions via a proprietary
  Web Services API.  You can use this library to access WebCalendar
  functions from within any Java application (including a servlet
  or JSP)
- The javadoc API for using the library
- Swing-based applications:
  + SampleApp: displays events (read-only) for a week or day, allows user to
    navigate next/previous.
  + ReminderApp: displays reminders in a popup at the appropriate time
  + ControlPanel: under development... not much there yet.
  + more to come soon.... ?

Will work with any java since 1.4.

The XML that is returned from the WebCalendar server is not SOAP.
It is just XML.  This may change in the future if additional functions are
added.  Until then SOAP is probably overkill and would require users to install
additional software (like the PEAR SOAP module) on their WebCalendar
server.


# Notes On The Sample App

SampleApp is intended to illustrate how to develop a WebCalendar client.
The app could be modified with a much improved UI.

This application may evolve into more than just a sample, I'm on the lookout
for an existing java-based calendar app where I could just replace/extend
the methods that get/update event data.  No need to rewrite a
complete UI if there is one out there already.



# Notes On The Reminder App

This ReminderApp client works independently from the email reminders that the
WebCalendar server sends out.  If you wish to use this instead of
the email reminders, you can turn off them in your WebCalendar
preferences.  If you do, be aware that you may miss a reminder if
this app is not running.


# Installation

First, make sure you version of WebCalendar has the "ws" subdirectory.
Version 1.0 or later should include these.  If you don't have these, you
will need to upgrade your WebCalendar installation.

Users should install the webcalendar.jar file locally and run it as
follows (for the ReminderApp, for SampleApp, just replace "ReminderApp"
with "SampleApp"):

## Web-based Authentication
    java -classpath webcalendar.jar us.k5n.webcalendar.ui.ReminderApp \
      -url=http://yourwebcalurlhere/ \
      -user=UUU -password=PPP
    [where "UUU" is the your username and "PPP" is your password]```

## Http-based Authentication
    java -classpath webcalendar.jar us.k5n.webcalendar.ui.ReminderApp \
      -url=http://yourwebcalurlhere/ \
      -httpuser=UUU -httppasswd=PPP
    [where "UUU" is the your username and "PPP" is your password]```

Note that you can use both web-based and HTTP-based authentication if
your site is configured to do that.  (The WebCalendar username will be
based on the -user argument.)


# License

The license for this code is the same GPL license used by WebCalendar.

# Compiling

You don't need to compile the java source code unless you want to make
some changes.  The code is already compiled into the webcalendar.jar file.

If you do want to compile, a makefile is provided.  If you have Windows,
you can use cygwin to get a copy of make, or you can just invoke javac
from the command line to build all the source.

