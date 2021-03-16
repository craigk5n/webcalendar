package us.k5n.webcalendar;

import java.net.MalformedURLException;
import java.net.URL;
import java.util.HashMap;

/**
 * ReminderLoader
 *
 * @author Craig Knudsen
 */
public class ReminderLoader extends Thread {
  private WebCalendarClient client;
  URL url;
  String username, password;
  int reloadMinutes;
  ReminderDisplayer display = null;
  HashMap done = null;

  public ReminderLoader ( WebCalendarClient client, ReminderDisplayer display,
      int reloadMinutes ) {
    super ();
    this.client = client;
    this.display = display;
    this.reloadMinutes = reloadMinutes;
    done = new HashMap ( 13 );
  }

  public ReminderList doUpdate () {
    try {
      String content = client.getReminders ();
      if (content.indexOf ( "<reminders>" ) >= 0) {
        ReminderList ret = new ReminderList ( content );
        return ( ret );
      } else {
        client.getMessageDisplayer ().showError (
            "Invalid data returned from server:\n\n" + content );
      }
    } catch ( MalformedURLException e ) {
      if (e.toString ().indexOf ( "login.php" ) >= 0) {
        // WebCalendar uses relative path for http redirect. This violates
        // the http protocol, but most browsers accept it. Java throws
        // an exception.
        client.getMessageDisplayer ().showError ( "WebCalendar login required" );
      } else {
        client.getMessageDisplayer ().showError (
            "Error getting data from server:\n" + e.toString () );
      }
    } catch ( WebCalendarErrorException e ) {
      System.err.println ( "WebCalendar Error: " + e.getMessage () );
      client.getMessageDisplayer ().showError (
          "WebCalendar Error:\n" + e.getMessage () );
    } catch ( Exception e ) {
      System.err.println ( "Exception getting reminders: " + e.toString () );
      e.printStackTrace ();
      if (e.toString ().indexOf ( "401" ) >= 0) {
        client.getMessageDisplayer ().showError (
            "Server requires HTTP authorization:\n" + e.toString () );
      } else {
        client.getMessageDisplayer ().showError (
            "Error getting data from server:\n" + e.toString () );
      }
    }
    return null;
  }

  public void run () {
    while ( true ) {
      ReminderList ret = doUpdate ();
      if (ret != null) {
        display.setReminders ( ret );
        display.interrupt ();
      }
      try {
        Thread.currentThread ().sleep ( reloadMinutes * 1000 * 60 );
      } catch ( InterruptedException e ) {
      }
    }
  }
}
