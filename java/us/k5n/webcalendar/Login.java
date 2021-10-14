package us.k5n.webcalendar;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLConnection;

/**
 * The Login class implements the login routine for WebCalendar for both
 * http-based and web-based setups.
 *
 * @author Craig Knudsen
 */
public class Login {
  URL url;
  String username, password; // WebCalendar username and password
  String httpUsername, httpPassword; // HTTP username and password
  ShowReminder show;
  LoginSession session = null;

  public Login ( ShowReminder show, URL url, String username, String password,
      String httpUsername, String httpPassword ) {
    super ();
    this.show = show;
    String urlStr = url.toString ();
    urlStr += "?login=" + username + "&password=" + password;
    try {
      this.url = new URL ( urlStr );
    } catch ( MalformedURLException e ) {
      System.err.println ( "Login URL: " + urlStr );
      System.err.println ( "Invalid URL for login: " + e.getMessage () );
      show.showError ( "Invalid URL for login: " + e.getMessage () );
      System.exit ( 1 );
    }
    this.username = username;
    this.password = password;
    this.httpUsername = httpUsername;
    this.httpPassword = httpPassword;
  }

  public boolean doLogin () {
    System.out.println ( "Logging into WebCalendar server..." );
    try {
      URLConnection urlc = url.openConnection ();
      urlc.setAllowUserInteraction ( true );
      if (httpUsername != null && httpPassword != null) {
        String userPass = httpUsername + ":" + httpPassword;
        String encoding = new sun.misc.BASE64Encoder ().encode ( userPass
            .getBytes () );
        urlc.setRequestProperty ( "Authorization", "Basic " + encoding );
      }
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
      System.out.println ( "Content:\n" + content );
      if (content.indexOf ( "<login>" ) >= 0) {
        session = new LoginSession ( content );
        return ( session != null );
      } else {
        show.showError ( "Invalid data returned from server:\n\n" + content );
      }
    } catch ( WebCalendarErrorException e ) {
      System.err.println ( "WebCalendar Error: " + e.getMessage () );
      show.showError ( "WebCalendar Error:\n" + e.getMessage () );
    } catch ( Exception e ) {
      System.err.println ( "Exception on login: " + e.toString () );
      e.printStackTrace ();
      if (e.toString ().indexOf ( "401" ) >= 0) {
        show.showError ( "Server requires HTTP authorization:\n"
            + e.toString () );
      } else {
        show.showError ( "Error getting data from server:\n" + e.toString () );
      }
    }
    return false;
  }

}
