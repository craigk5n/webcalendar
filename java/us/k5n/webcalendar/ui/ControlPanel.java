/*
 * $Id$
 *
 * Description:
 *	WebCalendar Admin Control Panel - used to administer WebCalendar
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

    JMenuItem item = new JMenuItem ( "Open WebCalendar URL..." );
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

  public static void main(String[] args) {
    String urlStr = null;
    URL loginURL = null, reminderURL = null;
    String wcUsername = null; // WebCalendar username
    String wcPassword = null; // WebCalendar password
    String username = null; // HTTP username
    String password = null; // HTTP password
    WebCalendarClient client = null; // WebCalendar client connection

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
        System.err.println ( "Invalid argument '" + args[i] + "'" );
        System.err.println (
          "Usage: java ControlPanel [options]" );
        System.err.println ( "  options:" );
        System.err.println ( "    -url=XXX" );
        System.err.println ( "    -username=XXX" );
        System.err.println ( "    -passwd=XXX" );
        System.err.println ( "    -httpusername=XXX" );
        System.err.println ( "    -httppasswd=XXX" );
        System.exit ( 1 );
      }
    }
    if ( urlStr == null ) {
      System.err.println ( "No URL specified." );
      System.exit ( 1 );
    }
    if ( ! urlStr.endsWith ( "/" ) ) {
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
          System.err.println ( "Invalid WebCalendar login" );
          System.exit ( 1 );
        }
      } catch ( Exception e ) {
        System.err.println ( "Error on WebCalendar login: " + e.toString() );
        System.exit ( 1 );
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

