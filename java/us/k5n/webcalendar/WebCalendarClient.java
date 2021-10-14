package us.k5n.webcalendar;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.StringBufferInputStream;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLConnection;
import java.net.URLEncoder;
import java.util.Calendar;
import java.util.Vector;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;

import org.w3c.dom.Document;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;

/**
 * Defines a client connection instance to the WebCalendar server.
 *
 * @author Craig Knudsen
 */
public class WebCalendarClient implements MessageDisplayer {
  private URL url;
  private String httpUsername = null;
  private String httpPassword = null;
  private String webcalUsername = null;
  private String webcalPassword = null;
  private static final String GET_REMINDERS = "get_reminders.php";
  private MessageDisplayer messageDisplayer = null;
  private String loginCookieName = null;
  private String loginCookieValue = null;
  private String calendarName = "WebCalendar";
  private String appName = "WebCalendar (Unknown Version)";
  private String appVersion = "Unknown Version";
  private String appDate = "Unknown Date";
  private boolean isAdmin = false; // is admin user?
  private boolean debugEnabled = true;
  private final static String LOGIN_REQUEST = "ws/login.php";
  private final static String EVENTS_REQUEST = "ws/get_events.php";
  private final static String REMINDER_REQUEST = "ws/get_reminders.php";
  private Vector listeners;

  /**
   * Create a WebCalendar client instance.
   *
   * @param url
   *          Base URL of WebCalendar. Should end with a trailing '/'.
   */
  public WebCalendarClient ( URL url ) {
    this.url = url;
    messageDisplayer = (MessageDisplayer)this;
    listeners = new Vector ();
  }

  /**
   * Get the URL of the WebCalendar server
   *
   * @return The WebCalendar server URL
   */
  public URL getURL () {
    return url;
  }

  /**
   * Set the username and password for HTTP-based authentication.
   *
   * @param username
   *          HTTP user login
   * @param password
   *          HTTP user password
   */
  public void setHttpAuthentication ( String username, String password ) {
    httpUsername = username;
    httpPassword = password;
  }

  /**
   * Set the username and password for web-based authentication.
   *
   * @param username
   *          WebCalendar user login
   * @param password
   *          WebCalendar user password
   */
  public void setWebAuthentication ( String username, String password ) {
    webcalUsername = username;
    webcalPassword = password;
  }

  /**
   * Set the MessageDisplayer object.
   */
  public void setMessageDisplayer ( MessageDisplayer messageDisplayer ) {
    this.messageDisplayer = messageDisplayer;
  }

  /**
   * Get the MessageDisplayer object.
   */
  public MessageDisplayer getMessageDisplayer () {
    return messageDisplayer;
  }

  /**
   * Login to the WebCalendar server. This is only required for web-based
   * authentication (and is not required for HTTP-based authentication or
   * single-user mode). On an invalid login, the MessageDisplayer object will be
   * used to display an error message.
   *
   * @return true on successful login, false otherwise
   */
  public boolean login () throws MalformedURLException, IOException {
    debug ( "Login to WebCalendar server..." );
    loginCookieName = loginCookieValue = null;
    try {
      URLConnection urlc = openConnection ( LOGIN_REQUEST + "?login="
          + webcalUsername + "&password=" + webcalPassword );
      StringBuffer data = new StringBuffer ();
      BufferedReader in = new BufferedReader ( new InputStreamReader ( urlc
          .getInputStream () ) );
      String line;
      while ( ( line = in.readLine () ) != null ) {
        data.append ( line );
        data.append ( "\n" );
      }
      in.close ();
      String content = data.toString ();
      debug ( "Content:\n" + content );
      if (content.indexOf ( "<login>" ) >= 0) {
        parseLoginContent ( content );
        return ( loginCookieValue != null );
      } else {
        messageDisplayer.showError ( "Invalid data returned from server:\n\n"
            + content );
      }
    } catch ( WebCalendarParseException e ) {
      messageDisplayer.showError ( "WebCalendar XML Error:\n" + e.toString () );
    } catch ( WebCalendarErrorException e ) {
      messageDisplayer.showError ( "WebCalendar Error:\n" + e.getMessage () );
    }
    return false; // did not login
  }

  private void parseLoginContent ( String xmlContent )
      throws WebCalendarParseException, WebCalendarErrorException {
    DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance ();
    try {
      DocumentBuilder builder = factory.newDocumentBuilder ();
      StringBufferInputStream is = new StringBufferInputStream ( xmlContent );
      Document document = builder.parse ( is );
      domToSession ( document );
    } catch ( SAXException sxe ) {
      // Error generated during parsing
      Exception x = sxe;
      if (sxe.getException () != null)
        x = sxe.getException ();
      x.printStackTrace ();
      throw new WebCalendarParseException (
          "Error parsing XML from WebCalendar server: " + x.toString () );
    } catch ( IOException ioe ) {
      ioe.printStackTrace ();
      throw new WebCalendarParseException (
          "I/O Error parsing XML from WebCalendar server: " + ioe.toString () );
    } catch ( ParserConfigurationException pce ) {
      pce.printStackTrace ();
      throw new WebCalendarParseException (
          "Parser Config Error parsing XML from WebCalendar server: "
              + pce.toString () );
    }
  }

  // Parse the XML returned from the login request
  private void domToSession ( Document document )
      throws WebCalendarParseException, WebCalendarErrorException {
    String error = Utils.getError ( document );
    if (error != null) {
      throw new WebCalendarErrorException ( error );
    }
    NodeList list = document.getElementsByTagName ( "login" );
    if (list.getLength () != 1) {
      System.err.println ( "Wrong number of <login> tags found" );
      throw new WebCalendarParseException (
          "Wrong number of <login> tags found" );
    }
    Node loginNode = list.item ( 0 );
    list = loginNode.getChildNodes ();
    for (int i = 0; i < list.getLength (); i++) {
      Node n = list.item ( i );
      if (n.getNodeType () == Node.ELEMENT_NODE) {
        if ("cookieName".equals ( n.getNodeName () )) {
          loginCookieName = Utils.xmlNodeGetValue ( n );
        } else if ("cookieValue".equals ( n.getNodeName () )) {
          loginCookieValue = Utils.xmlNodeGetValue ( n );
        } else if ("admin".equals ( n.getNodeName () )) {
          String adminStr = Utils.xmlNodeGetValue ( n );
          if (adminStr.equals ( "1" ) || adminStr.startsWith ( "Y" )
              || adminStr.startsWith ( "y" ))
            isAdmin = true;
        } else if ("calendarName".equals ( n.getNodeName () )) {
          calendarName = Utils.xmlNodeGetValue ( n );
        } else if ("appName".equals ( n.getNodeName () )) {
          appName = Utils.xmlNodeGetValue ( n );
        } else if ("appVersion".equals ( n.getNodeName () )) {
          appVersion = Utils.xmlNodeGetValue ( n );
        } else if ("appDate".equals ( n.getNodeName () )) {
          appDate = Utils.xmlNodeGetValue ( n );
        } else {
          System.err.println ( "Not sure what to do with <" + n.getNodeName ()
              + "> tag (expecting <cookieName>... ignoring)" );
        }
      }
    }
  }

  /**
   * Open up a URLConnection object to WebCalendar. User authentication actions
   * will be taken care of.
   *
   * @param request
   *          The filename and (optionally) querystring of the URL. (example:
   *          "login.php?username=xxx&password=yyy")
   */
  public URLConnection openConnection ( String urlFile )
      throws MalformedURLException, IOException {
    URL newUrl = new URL ( url, urlFile );
    URLConnection urlc = newUrl.openConnection ();
    urlc.setAllowUserInteraction ( true );
    if (httpUsername != null && httpPassword != null) {
      debug ( "Setting http authentication" );
      String userPass = httpUsername + ":" + httpPassword;
      String encoding = new sun.misc.BASE64Encoder ().encode ( userPass
          .getBytes () );
      urlc.setRequestProperty ( "Authorization", "Basic " + encoding );
    } else {
      debug ( "no http authentication" );
    }
    if (loginCookieName != null && loginCookieValue != null) {
      debug ( "Setting web authentication (session=" + loginCookieValue + ")" );
      urlc.setRequestProperty ( "Cookie", loginCookieName + "="
          + loginCookieValue );
    } else {
      debug ( "no web authentication (yet)" );
    }
    return urlc;
  }

  /**
   * Is the current user an admin user?
   *
   * public boolean isAdmin () { return admin; }
   *
   * /** Return the server's calendar name.
   */
  public String getCalendarName () {
    return calendarName;
  }

  /**
   * Return the server's WebCalendar name.
   */
  public String getServerName () {
    return appName;
  }

  /**
   * Return the server's WebCalendar version.
   */
  public String getServerVersion () {
    return appVersion;
  }

  /**
   * Return the server's WebCalendar version date.
   */
  public String getServerVersionDate () {
    return appDate;
  }

  /**
   * Send a request to the WebCalendar server to get pending reminders. Return
   * the XML response.
   */
  public String getReminders () throws MalformedURLException, IOException {
    debug ( "Getting reminders..." );
    return query ( REMINDER_REQUEST );
  }

  /**
   * Send a request to the WebCalendar server to get events for the specified
   * date range.
   *
   * @param startDate
   *          The start date in the range of events
   * @param endDate
   *          The end date in the range of events
   * @return The XML response
   */
  public String getEvents ( Calendar startDate, Calendar endDate )
      throws MalformedURLException, IOException {
    String startStr, endStr;

    startStr = Utils.CalendarToYYYYMMDD ( startDate );
    endStr = Utils.CalendarToYYYYMMDD ( endDate );
    debug ( "Getting events for " + startStr + " to " + endStr );
    return query ( EVENTS_REQUEST + "?startdate=" + startStr + "&enddate="
        + endStr );
  }

  /**
   * Get the results of a request. All communication to the WebCalendar server
   * should go through this method.
   *
   * @param urlFile
   *          The filename and (optionally) querystring of the URL. (example:
   *          "login.php?username=xxx&password=yyy")
   * @return The data sent back from the WebCalendar server (typically XML).
   */
  public String query ( String urlFile ) throws MalformedURLException,
      IOException {
    for (int i = 0; i < listeners.size (); i++) {
      WebCalendarClientListener listener = (WebCalendarClientListener)listeners
          .elementAt ( i );
      listener.outgoingRequest ( urlFile );
    }
    URLConnection urlc = openConnection ( urlFile );
    StringBuffer data = new StringBuffer ();
    BufferedReader in = new BufferedReader ( new InputStreamReader ( urlc
        .getInputStream () ) );
    String line;
    while ( ( line = in.readLine () ) != null ) {
      data.append ( line );
      data.append ( "\n" );
    }
    in.close ();
    String ret = data.toString ();
    for (int i = 0; i < listeners.size (); i++) {
      WebCalendarClientListener listener = (WebCalendarClientListener)listeners
          .elementAt ( i );
      listener.incomingResult ( ret );
    }
    return ret;
  }

  /**
   * Add a user
   *
   * @param user
   *          User to add
   * @return true on success, false on error
   */
  public boolean addUser ( User user ) {
    try {
      StringBuffer sb = new StringBuffer ( 50 );
      sb.append ( "ws/user_mod.php?add=1&username=" );
      sb.append ( user.login );
      if (user.firstName != null && user.firstName.length () > 0) {
        sb.append ( "&firstname=" );
        sb.append ( URLEncoder.encode ( user.firstName ) );
      }
      if (user.lastName != null && user.lastName.length () > 0) {
        sb.append ( "&lastname=" );
        sb.append ( URLEncoder.encode ( user.lastName ) );
      }
      if (user.fullName != null && user.fullName.length () > 0) {
        sb.append ( "&fullname=" );
        sb.append ( URLEncoder.encode ( user.fullName ) );
      }
      if (user.password != null && user.password.length () > 0) {
        sb.append ( "&password=" );
        sb.append ( URLEncoder.encode ( user.password ) );
      }
      if (user.email != null && user.email.length () > 0) {
        sb.append ( "&email=" );
        sb.append ( URLEncoder.encode ( user.email ) );
      }
      if (user.isAdmin) {
        sb.append ( "&admin=1" );
      }
      debug ( "Request: " + sb.toString () );
      String result = query ( sb.toString () );
      debug ( "Result:\n" + result );
      if (result.indexOf ( "success" ) >= 0) {
        return true;
      } else {
        // Error!
        int pos = result.indexOf ( "<error>" );
        int pos2 = result.indexOf ( "</error>" );
        String msg = "Error adding user: " + result;
        if (pos > 0 && pos2 > 0) {
          msg = result.substring ( pos + 7, pos2 );
        }
        messageDisplayer.showError ( msg );
      }
    } catch ( Exception e ) {
      showError ( "Error adding user:\n\n" + e.toString () );
      e.printStackTrace ();
    }
    return false;
  }

  /**
   * Delete a user
   *
   * @param user
   *          User to delete
   * @return true on success, false on error
   */
  public boolean deleteUser ( User user ) {
    try {
      StringBuffer sb = new StringBuffer ( 50 );
      sb.append ( "ws/user_mod.php?del=1&username=" );
      sb.append ( user.login );
      debug ( "Request: " + sb.toString () );
      String result = query ( sb.toString () );
      debug ( "Result:\n" + result );
      if (result.indexOf ( "success" ) >= 0) {
        return true;
      } else {
        // Error!
        int pos = result.indexOf ( "<error>" );
        int pos2 = result.indexOf ( "</error>" );
        String msg = "Error adding user: " + result;
        if (pos > 0 && pos2 > 0) {
          msg = result.substring ( pos + 7, pos2 );
        }
        messageDisplayer.showError ( msg );
      }
    } catch ( Exception e ) {
      showError ( "Error adding user:\n\n" + e.toString () );
      e.printStackTrace ();
    }
    return false;
  }

  /**
   * Update a user
   *
   * @param user
   *          User to update
   * @return true on success, false on error
   */
  public boolean updateUser ( User user ) {
    try {
      StringBuffer sb = new StringBuffer ( 50 );
      sb.append ( "ws/user_mod.php?username=" );
      sb.append ( user.login );
      if (user.firstName != null && user.firstName.length () > 0) {
        sb.append ( "&firstname=" );
        sb.append ( URLEncoder.encode ( user.firstName ) );
      }
      if (user.lastName != null && user.lastName.length () > 0) {
        sb.append ( "&lastname=" );
        sb.append ( URLEncoder.encode ( user.lastName ) );
      }
      if (user.fullName != null && user.fullName.length () > 0) {
        sb.append ( "&fullname=" );
        sb.append ( URLEncoder.encode ( user.fullName ) );
      }
      if (user.email != null && user.email.length () > 0) {
        sb.append ( "&email=" );
        sb.append ( URLEncoder.encode ( user.email ) );
      }
      if (user.isAdmin) {
        sb.append ( "&admin=1" );
      }
      debug ( "Request: " + sb.toString () );
      String result = query ( sb.toString () );
      debug ( "Result:\n" + result );
      if (result.indexOf ( "success" ) >= 0) {
        return true;
      } else {
        // Error!
        int pos = result.indexOf ( "<error>" );
        int pos2 = result.indexOf ( "</error>" );
        String msg = "Error adding user: " + result;
        if (pos > 0 && pos2 > 0) {
          msg = result.substring ( pos + 7, pos2 );
        }
        messageDisplayer.showError ( msg );
      }
    } catch ( Exception e ) {
      showError ( "Error updating user:\n\n" + e.toString () );
      e.printStackTrace ();
    }
    return false;
  }

  /**
   * Approve an event for the specified event participant.
   *
   * @param event
   *          Event to update
   * @param participant
   *          The event participant whose status we are modifying
   * @return true on success, false on error
   */
  public boolean approveEvent ( Event event, Participant participant ) {
    return updateEventStatus ( event, participant, "approve" );
  }

  /**
   * Reject an event for the specified event participant.
   *
   * @param event
   *          Event to update
   * @param participant
   *          The event participant whose status we are modifying
   * @return true on success, false on error
   */
  public boolean rejectEvent ( Event event, Participant participant ) {
    return updateEventStatus ( event, participant, "reject" );
  }

  /**
   * Delete an event for the specified event participant. This only marks the
   * event as deleted in the system. The event will still be in the database.
   *
   * @param event
   *          Event to update
   * @param participant
   *          The event participant whose status we are modifying
   * @return true on success, false on error
   */
  public boolean deleteEvent ( Event event, Participant participant ) {
    return updateEventStatus ( event, participant, "delete" );
  }

  /**
   * Update the status of an event (approve, reject, delete)
   *
   * @param event
   *          Event to update
   * @param participant
   *          The event participant whose status we are modifying
   * @param action
   *          The action ("approve", "reject", "delete")
   * @return true on success, false on error
   */
  private boolean updateEventStatus ( Event event, Participant participant,
      String action ) {
    try {
      StringBuffer sb = new StringBuffer ( 50 );
      sb.append ( "ws/event_mod.php?username=" );
      sb.append ( URLEncoder.encode ( participant.getLogin () ) );
      sb.append ( "&id=" );
      sb.append ( event.getId () );
      sb.append ( "&action=" );
      sb.append ( URLEncoder.encode ( action ) );
      debug ( "Request: " + sb.toString () );
      String result = query ( sb.toString () );
      debug ( "Result:\n" + result );
      if (result.indexOf ( "success" ) >= 0) {
        return true;
      } else {
        // Error!
        int pos = result.indexOf ( "<error>" );
        int pos2 = result.indexOf ( "</error>" );
        String msg = "Error updating event status: " + result;
        if (pos > 0 && pos2 > 0) {
          msg = result.substring ( pos + 7, pos2 );
        }
        messageDisplayer.showError ( msg );
      }
    } catch ( Exception e ) {
      showError ( "Error updating user:\n\n" + e.toString () );
      e.printStackTrace ();
    }
    return false;
  }

  private void debug ( String message ) {
    if (debugEnabled)
      System.out.println ( "[dbg] " + message );
  }

  /* ---- MessageDisplayer interface implementation ---- */

  public void showReminder ( Reminder reminder ) {
    if (messageDisplayer != null)
      messageDisplayer.showReminder ( reminder );
    else
      System.err.println ( "Reminder: " + reminder.toString () );
  }

  public void showMessage ( String message ) {
    if (messageDisplayer != null)
      messageDisplayer.showMessage ( message );
    else
      System.err.println ( message );
  }

  public void showError ( String message ) {
    if (messageDisplayer != null)
      messageDisplayer.showError ( message );
    else
      System.err.println ( "Error: " + message );
  }

  /**
   * Add a WebCalendarClientListener to be called for incoming/outgoing web
   * service messages.
   *
   * @param listener
   *          The WebCalendarClientListener listener to call on outgoing
   *          requests and incoming responses.
   */
  public void addListener ( WebCalendarClientListener listener ) {
    listeners.addElement ( listener );
  }

}
