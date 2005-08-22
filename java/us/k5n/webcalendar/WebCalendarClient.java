/*
 * $Id$
 */

package us.k5n.webcalendar;

import java.util.Calendar;
import java.net.*;
import java.io.*;
import javax.xml.parsers.*;
import org.xml.sax.*;
import org.w3c.dom.*;


/**
  * Defines a client connection instance to the WebCalendar server.
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
  private boolean isAdmin = false;
  private boolean debugEnabled = true;
  private final static String LOGIN_REQUEST = "ws/login.php";
  private final static String EVENTS_REQUEST = "ws/get_events.php";
  private final static String REMINDER_REQUEST = "ws/get_reminders.php";

  /**
    * Create a WebCalendar client instance.
    * @param url	Base URL of WebCalendar.
    *			Should end with a trailing '/'.
    */
  public WebCalendarClient ( URL url )
  {
    this.url = url;
    messageDisplayer = (MessageDisplayer) this;
  }

  /**
    * Get the URL of the WebCalendar server
    * @return	The WebCalendar server URL
    */
  public URL getURL ()
  {
    return url;
  }

  /**
    * Set the username and password for HTTP-based authentication.
    * @param username	HTTP user login
    * @param password	HTTP user password
    */
  public void setHttpAuthentication ( String username, String password )
  {
    httpUsername = username;
    httpPassword = password;
  }

  /**
    * Set the username and password for web-based authentication.
    * @param username	WebCalendar user login
    * @param password	WebCalendar user password
    */
  public void setWebAuthentication ( String username, String password )
  {
    webcalUsername = username;
    webcalPassword = password;
  }

  /**
    * Set the MessageDisplayer object.
    */
  public void setMessageDisplayer ( MessageDisplayer messageDisplayer )
  {
    this.messageDisplayer = messageDisplayer;
  }

  /**
    * Get the MessageDisplayer object.
    */
  public MessageDisplayer getMessageDisplayer ( )
  {
    return messageDisplayer;
  }

  /**
    * Login to the WebCalendar server.
    * This is only required for web-based authentication (and is
    * not required for HTTP-based authentication or single-user mode).
    * On an invalid login, the MessageDisplayer object will be used
    * to display an error message.
    * @return	true on successful login, false otherwise
    */
  public boolean login ()
    throws MalformedURLException, IOException
  {
    debug ( "Login to WebCalendar server..." );
    loginCookieName = loginCookieValue = null;
    try {
      URLConnection urlc = openConnection ( LOGIN_REQUEST + "?login=" +
        webcalUsername + "&password=" + webcalPassword );
      StringBuffer data = new StringBuffer ();
      BufferedReader in = new BufferedReader (
        new InputStreamReader ( urlc.getInputStream() ) );
      String line;
      while ( ( line = in.readLine() ) != null ) {
        data.append ( line );
        data.append ( "\n" );
      }
      in.close ();
      String content = data.toString ();
      debug ( "Content:\n" + content );
      if ( content.indexOf ( "<login>" ) >= 0 ) {
        parseLoginContent ( content );
        return ( loginCookieValue != null );
      } else {
        messageDisplayer.showError ( "Invalid data returned from server:\n\n" +
          content );
      }
    } catch ( WebCalendarParseException e ) {
      messageDisplayer.showError ( "WebCalendar XML Error:\n" + e.toString() );
    } catch ( WebCalendarErrorException e ) {
      messageDisplayer.showError ( "WebCalendar Error:\n" + e.getMessage() );
    }
    return false; // did not login
  }

  private void parseLoginContent ( String xmlContent )
    throws WebCalendarParseException, WebCalendarErrorException
  {
    DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance ();
    try {
      DocumentBuilder builder = factory.newDocumentBuilder ();
      StringBufferInputStream is = new StringBufferInputStream ( xmlContent );
      Document document = builder.parse ( is );
      domToSession ( document );
    } catch ( SAXException sxe ) {
      // Error generated during parsing
      Exception x = sxe;
      if ( sxe.getException() != null )
        x = sxe.getException ();
      x.printStackTrace ();
      throw new WebCalendarParseException (
        "Error parsing XML from WebCalendar server: " + x.toString() );
    } catch ( IOException ioe ) {
      ioe.printStackTrace ();
      throw new WebCalendarParseException (
        "I/O Error parsing XML from WebCalendar server: " + ioe.toString() );
    } catch ( ParserConfigurationException pce ) {
      pce.printStackTrace ();
      throw new WebCalendarParseException (
        "Parser Config Error parsing XML from WebCalendar server: " + pce.toString() );
    }
  }

  // Parse the XML returned from the login request
  private void domToSession ( Document document )
    throws WebCalendarParseException, WebCalendarErrorException
  {
    String error = Utils.getError ( document );
    if ( error != null ) {
      throw new WebCalendarErrorException ( error );
    }
    NodeList list = document.getElementsByTagName ( "login" );
    if ( list.getLength() != 1 ) {
      System.err.println ( "Wrong number of <login> tags found" );
      throw new WebCalendarParseException ( "Wrong number of <login> tags found" );
    }
    Node loginNode = list.item ( 0 );
    list = loginNode.getChildNodes ();
    for ( int i = 0; i < list.getLength(); i++ ) {
      Node n = list.item ( i );
      if ( n.getNodeType() == Node.ELEMENT_NODE ) {
        if ( "cookieName".equals ( n.getNodeName() ) ) {
          loginCookieName = Utils.xmlNodeGetValue ( n );
        } else if ( "cookieValue".equals ( n.getNodeName() ) ) {
          loginCookieValue = Utils.xmlNodeGetValue ( n );
        } else if ( "admin".equals ( n.getNodeName() ) ) {
          String val = Utils.xmlNodeGetValue ( n );
          if ( val != null && val.equals ( "1" ) )
            isAdmin = true;
        } else {
          System.err.println ( "Not sure what to do with <" +
            n.getNodeName() + "> tag (expecting <cookieName>... ignoring)" );
        }
      }
    }
  }

  /**
    * Open up a URLConnection object to WebCalendar.
    * User authentication actions will be taken care of.
    * @param request	The filename and (optionally) querystring
    *			of the URL.
    *			(example: "login.php?username=xxx&password=yyy")
    */
  public URLConnection openConnection ( String urlFile )
    throws MalformedURLException, IOException
  {
    URL newUrl = new URL ( url, urlFile );
    URLConnection urlc = newUrl.openConnection ();
    urlc.setAllowUserInteraction ( true );
    if ( httpUsername != null && httpPassword != null ) {
      debug ( "Setting http authentication" );
      String userPass = httpUsername + ":" + httpPassword;
      String encoding =
        new sun.misc.BASE64Encoder().encode ( userPass.getBytes() );
      urlc.setRequestProperty ( "Authorization", "Basic " + encoding );
    } else {
      debug ( "no http authentication" );
    }
    if ( loginCookieName != null && loginCookieValue != null ) {
      debug ( "Setting web authentication (session=" + loginCookieValue + ")" );
      urlc.setRequestProperty ( "Cookie",
        loginCookieName + "=" + loginCookieValue );
    } else {
      debug ( "no web authentication (yet)" );
    }
    return urlc;
  }

  /**
    * Send a request to the WebCalendar server to get pending reminders.
    * Return the XML response.
    */
  public String getReminders ()
    throws MalformedURLException, IOException
  {
    debug ( "Getting reminders..." );
    return query ( REMINDER_REQUEST );
  }

  /**
    * Send a request to the WebCalendar server to get events for
    * the specified date range.
    * @param startDate	The start date in the range of events
    * @param endDate	The end date in the range of events
    * @return	The XML response
    */
  public String getEvents ( Calendar startDate, Calendar endDate )
    throws MalformedURLException, IOException
  {
    String startStr, endStr;

    startStr = Utils.CalendarToYYYYMMDD ( startDate );
    endStr = Utils.CalendarToYYYYMMDD ( endDate );
    debug ( "Getting events for " + startStr + " to " + endStr );
    return query ( EVENTS_REQUEST + "?startdate=" + startStr +
      "&enddate=" + endStr );
  }

  /**
    * Get the results of a request.
    * @param request	The filename and (optionally) querystring
    *			of the URL.
    *			(example: "login.php?username=xxx&password=yyy")
    * @return	The data sent back from the WebCalendar server
    *		(typically XML).
    */
  public String query ( String urlFile )
    throws MalformedURLException, IOException
  {
    URLConnection urlc = openConnection ( urlFile );
    StringBuffer data = new StringBuffer ();
    BufferedReader in = new BufferedReader (
      new InputStreamReader ( urlc.getInputStream() ) );
    String line;
    while ( ( line = in.readLine() ) != null ) {
      data.append ( line );
      data.append ( "\n" );
    }
    in.close ();
    return data.toString ();
  }

  private void debug ( String message )
  {
    if ( debugEnabled )
      System.out.println ( "[dbg] " + message );
  }

  /* ---- MessageDisplayer interface implementation ---- */

  public void showReminder ( Reminder reminder )
  {
    System.err.println ( "Reminder: " + reminder.toString() );
  }

  public void showMessage ( String message )
  {
    System.err.println ( message );
  }

  public void showError ( String message )
  {
    System.err.println ( "Error: " + message );
  }


}

