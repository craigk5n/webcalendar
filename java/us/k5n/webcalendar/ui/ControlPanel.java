/*
 * $Id$
 *
 * Description:
 *	WebCalendar Admin ControlPanel - used to administer WebCalendar
 *
 * THIS IS STILL UNDER CONSTRUCTION...
 *
 * History:
 *	23-Aug-2005	cknudsen@cknudsen.com
 *			Created
 */

package us.k5n.webcalendar.ui;

import java.util.Calendar;
import java.net.*;
import java.awt.*;
import java.awt.event.*;
import javax.swing.*;        
import us.k5n.webcalendar.*;


/**
  * WebCalendar Control Panel using Web Services client
  */
public class ControlPanel
  implements MessageDisplayer, EventDisplayer, ActionListener {
  WebCalendarClient client = null;
  String appName = "WebCalendar ControlPanel";
  EventLoader eloader = null;
  EventList events = null;
  JFrame startupStatusFrame = null;
  JFrame toplevel = null;
  JLabel dateRange = null;
  JLabel statusMessage = null;
  Calendar startDate = null, endDate = null;
  // User tab
  JPanel userTab = null;
  JList userTabUserList = null;

  /**
    * Show a reminder on the screen.
    * @param reminder	The reminder to display
    */
  public void showReminder ( Reminder reminder )
  {
    JOptionPane.showMessageDialog ( null, reminder.toString() );
  }

  /**
    * Show a message on the screen.
    * @param message	The message to display
    */
  public void showMessage ( String message )
  {
    JOptionPane.showMessageDialog ( null, message );
  }

  /**
    * Show an error on the screen.
    * @param message	The error message to display
    */
  public void showError ( String message )
  {
    JOptionPane.showMessageDialog ( null, message, appName + " Error",
      JOptionPane.WARNING_MESSAGE );
  }

  /**
    * Receive an incoming list of events.
    */
  public void storeEvents ( EventList events )
  {
    this.events = events;

    // remove startup message if it exists
    if ( startupStatusFrame != null ) {
      startupStatusFrame.dispose ();
      startupStatusFrame = null;
    }

    // Create main window if not yet done
    if ( toplevel == null )
      createWindow ( client );

    // Update event display
    StringBuffer sb = new StringBuffer ();
    for ( int i = 0; i < events.size(); i++ ) {
      sb.append ( events.eventAt ( i ).toString() + "\n" );
    }
    if ( sb.length() == 0 )
      sb.append ( "No events" );
    //workArea.setText ( sb.toString() );
    // update date range label
    dateRange.setText ( "Date range: " +
      ( startDate.get ( Calendar.MONTH ) + 1 ) + '/' +
      startDate.get ( Calendar.DAY_OF_MONTH ) + '/' +
      startDate.get ( Calendar.YEAR ) + " - " +
      ( endDate.get ( Calendar.MONTH ) + 1 ) + '/' +
      endDate.get ( Calendar.DAY_OF_MONTH ) + '/' +
      endDate.get ( Calendar.YEAR ) );
  }

  /** Create the main window */
  public void createWindow ( WebCalendarClient client ) {
    createWindow ( 700, 500, client );
  }

  /** Create the main window */
  public void createWindow ( int width, int height, WebCalendarClient client )
  {
    JMenuItem item;
    this.client = client;
    toplevel = new JFrame ( appName );
    JPanel mainPanel = new JPanel ();
    mainPanel.setLayout ( new BorderLayout () );

    // add menu bar
    JMenuBar menubar = new JMenuBar ();
    toplevel.setJMenuBar ( menubar );
    JMenu menu = new JMenu ( "File" );
    menu.setMnemonic ( KeyEvent.VK_F );
    menubar.add ( menu );

    /*
    item = new JMenuItem ( "Open WebCalendar URL..." );
    item.setAccelerator ( KeyStroke.getKeyStroke (
      KeyEvent.VK_O, ActionEvent.ALT_MASK ) );
    item.addActionListener (
      new ActionListener () {
        public void actionPerformed ( ActionEvent e ) {
          System.out.println ( "not yet implemented" );
        }
      }
    );
    menu.add ( item );
    menu.addSeparator ();
    */

    item = new JMenuItem ( "Exit" );
    item.setAccelerator ( KeyStroke.getKeyStroke (
      KeyEvent.VK_X, ActionEvent.ALT_MASK ) );
    item.addActionListener ( this );
    menu.add ( item );

    String msg = appName + " / " +
      client.getCalendarName () + " /" +
      client.getServerName ();
    statusMessage = new JLabel ( msg );
    mainPanel.add ( statusMessage, BorderLayout.SOUTH );

    JTabbedPane tabs = new JTabbedPane ();
    mainPanel.add ( tabs, BorderLayout.CENTER );
    userTab = createUserTab ();
    tabs.addTab ( "Users", userTab );
    tabs.addTab ( "Groups", new JPanel () );
    tabs.addTab ( "Settings", new JPanel () );
    //tabs.addTab ( "Assistants", new JPanel () );
    tabs.addTab ( "NonUser Calendars", new JPanel () );
    tabs.addTab ( "Categories", new JPanel () );
    //tabs.addTab ( "Views", new JPanel () );
    //tabs.addTab ( "Layers", new JPanel () );
    //tabs.addTab ( "Reports", new JPanel () );
    //tabs.addTab ( "Delete Events", new JPanel () );
    tabs.addTab ( "Activity Log", new JPanel () );
    //tabs.addTab ( "Appearance", new JPanel () );
    tabs.addTab ( "Unapproved Events", new JPanel () );

    Container contentPane = toplevel.getContentPane();
    contentPane.setLayout ( new GridLayout ( 1, 1 ) );
    contentPane.add ( mainPanel );

    // Exit when the window is closed.
    toplevel.setDefaultCloseOperation ( JFrame.EXIT_ON_CLOSE );

    toplevel.setSize ( width, height );
    toplevel.setVisible ( true );
  }

  // TODO - load users in another thread
  JPanel createUserTab ()
  {
    JPanel ret;

    ret = new JPanel ();

    ret.setLayout ( new BorderLayout () );
    JPanel cmdPanel = new JPanel ();
    cmdPanel.setLayout ( new FlowLayout () );

    JButton b = new JButton ( "Add..." );
    b.setEnabled ( false );
    cmdPanel.add ( b ); // TODO

    b = new JButton ( "Import..." );
    b.setEnabled ( false );
    cmdPanel.add ( b ); // TODO

    b = new JButton ( "Edit" );
    b.setEnabled ( false );
    cmdPanel.add ( b ); // TODO

    b = new JButton ( "Delete" );
    b.setEnabled ( false );
    cmdPanel.add ( b ); // TODO

    ret.add ( cmdPanel, BorderLayout.SOUTH );

    UserList list = null;
    try {
      String userText = client.query ( "ws/get_users.php" );
      list = new UserList ( userText, "users" );
    } catch ( Exception e ) {
      System.err.println ( "Exception getting users: " + e );
      e.printStackTrace ();
    }
    JList userTabUserList = new JList ( list );
    ret.add ( userTabUserList, BorderLayout.CENTER );

    return ret;
  }

  private void reloadEvents ()
  {
    eloader.setStartDate ( startDate );
    eloader.setEndDate ( endDate );
    eloader.interrupt ();
  }

  /**
    * Event handler (implementation of ActionListener interface)
    */
  public void actionPerformed ( ActionEvent e ) {
    if ( "Exit".equals ( e.getActionCommand() ) ) {
      System.exit ( 0 );
    } else {
      System.out.println ( "action command: " + e.getActionCommand() );
      System.out.println ( "paramString: " + e.paramString() );
    }
  }


  /**
    * Show an error on the screen.
    * @param message	The error message to display
    */
  public static void fatalError ( String message )
  {
    System.err.println ( message );
    JOptionPane.showMessageDialog ( null, message,
      "WebCalendar ControlPanel Error",
      JOptionPane.WARNING_MESSAGE );
    System.exit ( 1 );
  }

  /**
    * Prompt the user for a text response.
    * @param message	The prompt to provide the user
    * @return		The text the user provided
    */
  public static String promptUser ( String message )
  {
    String s = (String)JOptionPane.showInputDialog (
      null, message, "WebCalendar ControlPanel",
      JOptionPane.PLAIN_MESSAGE, null, null, "");
    return s;
  }


  public static void main(String[] args) {
    String urlStr = null;
    URL loginURL = null, reminderURL = null;
    String wcUsername = null; // WebCalendar username
    String wcPassword = null; // WebCalendar password
    String username = null; // HTTP username
    String password = null; // HTTP password
    WebCalendarClient client = null; // WebCalendar client connection

    System.out.println ( "WebCalendar ControlPanel version: " +
      "$Id$" );

    for ( int i = 0; i < args.length; i++ ) {
      if ( args[i].startsWith ( "-url=" ) ) {
        urlStr = args[i].substring ( 5 );
      } else if ( args[i].startsWith ( "-user=" ) ) {
        wcUsername = args[i].substring ( 6 );
      } else if ( args[i].startsWith ( "-username=" ) ) {
        wcUsername = args[i].substring ( 10 );
      } else if ( args[i].startsWith ( "-password=" ) ) {
        wcPassword = args[i].substring ( 10 );
      } else if ( args[i].startsWith ( "-passwd=" ) ) {
        wcPassword = args[i].substring ( 8 );
      } else if ( args[i].startsWith ( "-httpuser=" ) ) {
        username = args[i].substring ( 10 );
      } else if ( args[i].startsWith ( "-httpusername=" ) ) {
        username = args[i].substring ( 14 );
      } else if ( args[i].startsWith ( "-httppasswd=" ) ) {
        password = args[i].substring ( 12 );
      } else if ( args[i].startsWith ( "-httppassword=" ) ) {
        password = args[i].substring ( 14 );
      } else {
        StringBuffer sb = new StringBuffer ( 128 );
        sb.append ( "Invalid argument '" + args[i] + "'\n" );
        sb.append (
          "Usage: java ControlPanel [options]\n" );
        sb.append ( "  options:\n" );
        sb.append ( "    -url=XXX\n" );
        sb.append ( "    -username=XXX\n" );
        sb.append ( "    -passwd=XXX\n" );
        sb.append ( "    -httpusername=XXX\n" );
        sb.append ( "    -httppasswd=XXX\n" );
        String msg = sb.toString ();
        fatalError ( msg );
      }
    }
    if ( urlStr == null ) {
      fatalError ( "No URL specified." );
    }
    if ( ! urlStr.endsWith ( "/" ) ) {
      fatalError ( "Invalid WebCalendar URL.\n" +
        "Should be base URL (ends with '/')" );
    }

    URL url = null;
    try {
      url = new URL ( urlStr );
    } catch ( Exception e ) {
      fatalError ( "Invalid URL: " + urlStr );
    }

System.out.println ( "username: " + username );
System.out.println ( "wcUsername: " + wcUsername );

    // TODO: don't display password as users type them
    // If no http password but we have a http username,
    // prompt for them now...
    while ( username != null &&
      ( password == null || password.length() == 0 ) ) {
      password = promptUser ( "Your server requires HTTP authentication.\n\n" +
        "Please enter the password\nfor user \"" +
        username + "\".\n\nPassword:" );
    }

    // If no http username or webcal username, let's assume they are using
    // web-based auth and ask for a webcal username.
    if ( ( wcUsername == null || wcUsername.length() == 0 ) &&
     ( username == null || username.length() == 0 ) ) {
      while ( wcUsername == null || wcUsername.length() == 0 ) {
        wcUsername = promptUser (
          "Please enter your\nWebCalendar username.\n\nLogin:" );
      }
    }

    // If no webcal password but we have a webcal username,
    // prompt for them now...
    while ( wcUsername != null &&
      ( wcPassword == null || wcPassword.length() == 0 ) ) {
      wcPassword = promptUser ( "Please enter the password\nfor user \"" +
        wcUsername + "\".\n\nPassword:" );
    }

    client = new WebCalendarClient ( url );

    ControlPanel app = new ControlPanel ();
    client.setMessageDisplayer ( (MessageDisplayer)app );

    // Display a message indicating we are connecting...
    app.startupStatusFrame = new JFrame(app.appName);
    JLabel label = new JLabel("Connecting to WebCalendar server...");
    app.startupStatusFrame.getContentPane().add(label);
    app.startupStatusFrame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
    app.startupStatusFrame.pack();
    app.startupStatusFrame.setLocation ( 300, 300 );
    app.startupStatusFrame.setVisible(true);
    app.startupStatusFrame.toFront ();

    if ( username != null )
      client.setHttpAuthentication ( username, password );

    // If we need to login to webcalendar, do it now
    if ( wcUsername != null ) {
      client.setWebAuthentication ( wcUsername, wcPassword );
      try {
        label.setText ( "Logging in to server..." );
        if ( ! client.login () ) {
          fatalError ( "Invalid WebCalendar login" );
        }
      } catch ( Exception e ) {
        e.printStackTrace ();
        fatalError ( "Error on WebCalendar login: " + e.toString() );
      }
    }

    // remove startup message if it exists
    if ( app.startupStatusFrame != null ) {
      app.startupStatusFrame.dispose ();
      app.startupStatusFrame = null;
    }

    app.createWindow ( 700, 500, client );
  }
}

