package us.k5n.webcalendar.ui;

import java.awt.BorderLayout;
import java.awt.Container;
import java.awt.GridLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.KeyEvent;
import java.net.URL;
import java.util.Calendar;

import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JMenu;
import javax.swing.JMenuBar;
import javax.swing.JMenuItem;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTextArea;
import javax.swing.KeyStroke;

import us.k5n.webcalendar.EventDisplayer;
import us.k5n.webcalendar.EventList;
import us.k5n.webcalendar.EventLoader;
import us.k5n.webcalendar.MessageDisplayer;
import us.k5n.webcalendar.Reminder;
import us.k5n.webcalendar.ReminderDisplayer;
import us.k5n.webcalendar.ReminderLoader;
import us.k5n.webcalendar.Utils;
import us.k5n.webcalendar.WebCalendarClient;

/**
 * WebCalendar Web Services sample client application. This application lists
 * events.
 *
 * @author Craig Knudsen
 */
public class SampleApp implements MessageDisplayer, EventDisplayer,
    ActionListener {
  String appName = "WebCalendar Client";
  EventLoader eloader = null;
  EventList events = null;
  JFrame startupStatusFrame = null;
  JFrame toplevel = null;
  JTextArea workArea = null;
  JLabel dateRange = null;
  JLabel statusMessage = null;
  Calendar startDate = null, endDate = null;

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

  /**
   * Receive an incoming list of events.
   */
  public void storeEvents ( EventList events ) {
    this.events = events;

    // remove startup message if it exists
    if (startupStatusFrame != null) {
      startupStatusFrame.dispose ();
      startupStatusFrame = null;
    }

    // Create main window if not yet done
    if (toplevel == null)
      createWindow ();

    // Update event display
    StringBuffer sb = new StringBuffer ();
    for (int i = 0; i < events.size (); i++) {
      sb.append ( events.eventAt ( i ).toString () + "\n" );
    }
    if (sb.length () == 0)
      sb.append ( "No events" );
    workArea.setText ( sb.toString () );
    // update date range label
    dateRange.setText ( "Date range: "
        + ( startDate.get ( Calendar.MONTH ) + 1 ) + '/'
        + startDate.get ( Calendar.DAY_OF_MONTH ) + '/'
        + startDate.get ( Calendar.YEAR ) + " - "
        + ( endDate.get ( Calendar.MONTH ) + 1 ) + '/'
        + endDate.get ( Calendar.DAY_OF_MONTH ) + '/'
        + endDate.get ( Calendar.YEAR ) );
  }

  /** Create the main window */
  public void createWindow () {
    toplevel = new JFrame ( appName );
    JPanel mainPanel = new JPanel ();
    mainPanel.setLayout ( new BorderLayout () );

    // add menu bar
    JMenuBar menubar = new JMenuBar ();
    toplevel.setJMenuBar ( menubar );
    JMenu menu = new JMenu ( "File" );
    menu.setMnemonic ( KeyEvent.VK_F );
    menubar.add ( menu );

    JMenuItem item = new JMenuItem ( "Open WebCalendar URL..." );
    item.setAccelerator ( KeyStroke.getKeyStroke ( KeyEvent.VK_O,
        ActionEvent.ALT_MASK ) );
    item.addActionListener ( new ActionListener () {
      public void actionPerformed ( ActionEvent e ) {
        System.out.println ( "not yet implemented" );
      }
    } );
    menu.add ( item );
    menu.addSeparator ();

    item = new JMenuItem ( "Exit" );
    item.setAccelerator ( KeyStroke.getKeyStroke ( KeyEvent.VK_X,
        ActionEvent.ALT_MASK ) );
    item.addActionListener ( this );
    menu.add ( item );

    menu = new JMenu ( "Go to" );
    menu.setMnemonic ( KeyEvent.VK_G );
    menubar.add ( menu );

    item = new JMenuItem ( "Today" );
    item.addActionListener ( this );
    menu.add ( item );

    item = new JMenuItem ( "Next Day" );
    item.addActionListener ( this );
    menu.add ( item );

    item = new JMenuItem ( "Previous Day" );
    item.addActionListener ( this );
    menu.add ( item );

    item = new JMenuItem ( "This Week" );
    item.addActionListener ( this );
    menu.add ( item );

    item = new JMenuItem ( "Next Week" );
    // item.setAccelerator ( KeyStroke.getKeyStroke (
    // KeyEvent.VK_N, ActionEvent.ALT_MASK ) );
    item.addActionListener ( this );
    menu.add ( item );

    item = new JMenuItem ( "Previous Week" );
    // item.setAccelerator ( KeyStroke.getKeyStroke (
    // KeyEvent.VK_P, ActionEvent.ALT_MASK ) );
    item.addActionListener ( this );
    menu.add ( item );

    dateRange = new JLabel ();
    mainPanel.add ( dateRange, BorderLayout.NORTH );

    statusMessage = new JLabel ( "Welcome to " + appName );
    mainPanel.add ( statusMessage, BorderLayout.SOUTH );

    workArea = new JTextArea ( 20, 50 );
    workArea.setEditable ( false );
    JScrollPane scrollPane = new JScrollPane ( workArea );
    mainPanel.add ( scrollPane, BorderLayout.CENTER );

    Container contentPane = toplevel.getContentPane ();
    contentPane.setLayout ( new GridLayout ( 1, 1 ) );
    contentPane.add ( mainPanel );

    // Exit when the window is closed.
    toplevel.setDefaultCloseOperation ( JFrame.EXIT_ON_CLOSE );

    toplevel.pack ();
    toplevel.setVisible ( true );
  }

  private void reloadEvents () {
    eloader.setStartDate ( startDate );
    eloader.setEndDate ( endDate );
    eloader.interrupt ();
  }

  /**
   * Event handler (implementation of ActionListener interface)
   */
  public void actionPerformed ( ActionEvent e ) {
    if ("Exit".equals ( e.getActionCommand () )) {
      System.exit ( 0 );
    } else if ("Today".equals ( e.getActionCommand () )) {
      startDate = Calendar.getInstance ();
      endDate = Calendar.getInstance ();
      reloadEvents ();
    } else if ("Next Day".equals ( e.getActionCommand () )) {
      startDate.add ( Calendar.DATE, 1 );
      endDate = (Calendar)startDate.clone ();
      reloadEvents ();
    } else if ("Previous Day".equals ( e.getActionCommand () )) {
      startDate.add ( Calendar.DATE, -1 );
      endDate = (Calendar)startDate.clone ();
      reloadEvents ();
    } else if ("This Week".equals ( e.getActionCommand () )) {
      startDate = Calendar.getInstance ();
      startDate = Utils.startOfWeek ( startDate, false );
      endDate = Utils.endOfWeek ( startDate, false );
      reloadEvents ();
    } else if ("Next Week".equals ( e.getActionCommand () )) {
      startDate.add ( Calendar.DATE, 7 );
      startDate = Utils.startOfWeek ( startDate, false );
      endDate = Utils.endOfWeek ( startDate, false );
      reloadEvents ();
    } else if ("Previous Week".equals ( e.getActionCommand () )) {
      startDate.add ( Calendar.DATE, -7 );
      startDate = Utils.startOfWeek ( startDate, false );
      endDate = Utils.endOfWeek ( startDate, false );
      reloadEvents ();
    } else {
      System.out.println ( "action command: " + e.getActionCommand () );
      System.out.println ( "paramString: " + e.paramString () );
    }
  }

  public static void main ( String[] args ) {
    String urlStr = null;
    URL loginURL = null, reminderURL = null;
    int reloadReminderMinutes = 15; // how often to get updated list of
                                    // reminders
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
      } else if (args[i].startsWith ( "-username=" )) {
        wcUsername = args[i].substring ( 10 );
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
      } else {
        System.err.println ( "Invalid argument '" + args[i] + "'" );
        System.err.println ( "Usage: java SampleApp [options]" );
        System.err.println ( "  options:" );
        System.err.println ( "    -username=XXX" );
        System.err.println ( "    -passwd=XXX" );
        System.err.println ( "    -httpusername=XXX" );
        System.err.println ( "    -httppasswd=XXX" );
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

    SampleApp app = new SampleApp ();
    client.setMessageDisplayer ( (MessageDisplayer)app );

    // Display a message indicating we are connecting...
    app.startupStatusFrame = new JFrame ( app.appName );
    JLabel label = new JLabel ( "Connecting to WebCalendar server..." );
    app.startupStatusFrame.getContentPane ().add ( label );
    app.startupStatusFrame.setDefaultCloseOperation ( JFrame.EXIT_ON_CLOSE );
    app.startupStatusFrame.pack ();
    app.startupStatusFrame.setLocation ( 300, 300 );
    app.startupStatusFrame.setVisible ( true );
    app.startupStatusFrame.toFront ();

    if (username != null)
      client.setHttpAuthentication ( username, password );

    // If we need to login to webcalendar, do it now
    if (wcUsername != null) {
      client.setWebAuthentication ( wcUsername, wcPassword );
      try {
        label.setText ( "Logging in to server..." );
        if (!client.login ()) {
          System.err
              .println ( "Invalid WebCalendar login '" + wcUsername + "'" );
          System.exit ( 1 );
        }
      } catch ( Exception e ) {
        System.err.println ( "Error on WebCalendar login: " + e.toString () );
        System.exit ( 1 );
      }
    }

    ReminderDisplayer reminderDisplay = new ReminderDisplayer ( client );
    ReminderLoader rloader = new ReminderLoader ( client, reminderDisplay,
        reloadReminderMinutes );
    rloader.start ();

    label.setText ( "Loading events..." );
    app.eloader = new EventLoader ( client, (EventDisplayer)app );
    Calendar today = Calendar.getInstance ();
    app.startDate = Utils.startOfWeek ( today, true );
    app.eloader.setStartDate ( app.startDate );
    app.endDate = Utils.endOfWeek ( today, true );
    app.eloader.setEndDate ( app.endDate );
    app.eloader.start ();
  }
}
