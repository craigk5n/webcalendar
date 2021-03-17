package us.k5n.webcalendar;

/**
 * An error message was returned from WebCalendar server.
 *
 * @author Craig Knudsen
 */
public class WebCalendarErrorException extends Exception {

  public WebCalendarErrorException () {
    super ();
  }

  public WebCalendarErrorException ( String msg ) {
    super ( msg );
  }

}
