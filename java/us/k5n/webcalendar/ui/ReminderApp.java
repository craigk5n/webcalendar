package us.k5n.webcalendar.ui;

import java.net.URL;

import javax.swing.JOptionPane;

import us.k5n.webcalendar.MessageDisplayer;
import us.k5n.webcalendar.Reminder;
import us.k5n.webcalendar.ReminderDisplayer;
import us.k5n.webcalendar.ReminderLoader;
import us.k5n.webcalendar.WebCalendarClient;

/**
 * WebCalendar Web Services client for displaying event reminders. Queries the
 * WebCalendar server to get a list of upcoming reminders and then displays them
 * at the proper time.
 *
 * @author Craig Knudsen
 */
public class ReminderApp implements MessageDisplayer {
  String appName = "WebCalendar";

  /**
   * Show a reminder on the screen.
   *
   * @param reminder
   *          The reminder to display
   */
  public void showReminder ( Reminder reminder ) {
    JOptionPane.showMessageDialog ( null, reminder.toString () );
  }

  /**
   * Show a message on the screen.
   *
   * @param message
   *          The message to display
   */
  public void showMessage ( String message ) {
    JOptionPane.showMessageDialog ( null, message );
  }

  /**
   * Show an error on the screen.
   *
   * @param message
   *          The error message to display
   */
  public void showError ( String message ) {
    JOptionPane.showMessageDialog ( null, message, appName + " Error",
        JOptionPane.WARNING_MESSAGE );
  }

  public static void main ( String[] args ) {
    String urlStr = null;
    URL loginURL = null, reminderURL = null;
    int reloadMinutes = 15; // how often to get updated list of reminders
    String wcUsername = null; // WebCalendar username
    String wcPassword = null; // WebCalendar password
    String username = null; // HTTP username
    String password = null; // HTTP password
    WebCalendarClient client = null; // WebCalendar client connection

    for (int i = 0; i < args.length; i++) {
      if (args[i].startsWith ( "-url=" )) {
        urlStr = args[i].substring ( 5 );
      } else if (args[i].startsWith ( "-user=" )) {
        wcUsername = args[i].substring ( 6 );
      } else if (args[i].startsWith ( "-password=" )) {
        wcPassword = args[i].substring ( 10 );
      } else if (args[i].startsWith ( "-passwd=" )) {
        wcPassword = args[i].substring ( 8 );
      } else if (args[i].startsWith ( "-httpuser=" )) {
        username = args[i].substring ( 10 );
      } else if (args[i].startsWith ( "-httpusername=" )) {
        username = args[i].substring ( 14 );
      } else if (args[i].startsWith ( "-httppasswd=" )) {
        password = args[i].substring ( 12 );
      } else if (args[i].startsWith ( "-httppassword=" )) {
        password = args[i].substring ( 14 );
      } else if (args[i].startsWith ( "-reload=" )) {
        reloadMinutes = Integer.parseInt ( args[i].substring ( 8 ) );
      } else {
        System.err.println ( "Invalid argument '" + args[i] + "'" );
        System.err.println ( "Usage: java ReminderApp [options]" );
        System.err.println ( "  options:" );
        System.err.println ( "    -username=XXX" );
        System.err.println ( "    -passwd=XXX" );
        System.err.println ( "    -httpusername=XXX" );
        System.err.println ( "    -httppasswd=XXX" );
        System.err.println ( "    -reload=N" );
        System.exit ( 1 );
      }
    }
    if (urlStr == null) {
      System.err.println ( "No URL specified." );
      System.exit ( 1 );
    }
    if (!urlStr.endsWith ( "/" )) {
      System.err.println ( "Invalid WebCalendar URL." );
      System.err.println ( "Should be base URL (ends with '/')" );
      System.exit ( 1 );
    }
    try {
      URL url = new URL ( urlStr );
      client = new WebCalendarClient ( url );
    } catch ( Exception e ) {
      System.err.println ( "Invalid URL: " + urlStr );
      System.exit ( 1 );
    }

    ReminderApp app = new ReminderApp ();
    client.setMessageDisplayer ( (MessageDisplayer)app );

    ReminderDisplayer display = new ReminderDisplayer ( client );
    display.start ();

    if (username != null)
      client.setHttpAuthentication ( username, password );

    // If we need to login to webcalendar, do it now
    if (wcUsername != null) {
      client.setWebAuthentication ( wcUsername, wcPassword );
      try {
        if (!client.login ()) {
          System.err.println ( "Invalid WebCalendar login" );
          System.exit ( 1 );
        }
      } catch ( Exception e ) {
        System.err.println ( "Error on WebCalendar login: " + e.toString () );
        System.exit ( 1 );
      }
    }

    ReminderLoader loader = new ReminderLoader ( client, display, reloadMinutes );
    loader.start ();

    // app.showReminder ( "This is example code!\n\nTest 1, 2, 3..." );
  }
}
