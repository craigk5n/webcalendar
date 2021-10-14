package us.k5n.webcalendar;

import java.net.MalformedURLException;
import java.net.URL;
import java.util.Calendar;

/**
 * Loads a list of events from the server in a thread.
 *
 * @author Craig Knudsen
 */
public class EventLoader extends Thread {
  private WebCalendarClient client;
  URL url;
  String username, password;
  EventDisplayer display = null;
  Calendar startDate = null, endDate = null;

  public EventLoader ( WebCalendarClient client, EventDisplayer display ) {
    super ();
    this.client = client;
    this.display = display;
    startDate = Calendar.getInstance ();
    endDate = Calendar.getInstance ();
  }

  public EventList getEvents () {
    // System.out.println ( "Getting events..." );
    try {
      String content = client.getEvents ( startDate, endDate );
      if (content.indexOf ( "<events>" ) >= 0) {
        EventList ret = new EventList ( content, "events" );
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

  public void setStartDate ( Calendar startDate ) {
    this.startDate = startDate;
  }

  public void setEndDate ( Calendar endDate ) {
    this.endDate = endDate;
  }

  public void run () {
    while ( true ) {
      EventList ret = getEvents ();
      if (ret != null) {
        display.storeEvents ( ret );
      }
      try {
        // sleep forever.... or a year (until interrupted)
        Thread.currentThread ().sleep ( 365 * 24 * 3600 * 1000 );
      } catch ( InterruptedException e ) {
      }
    }
  }
}
