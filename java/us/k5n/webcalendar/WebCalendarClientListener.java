package us.k5n.webcalendar;

/**
 * The WebCalendarClientListener defines an interface for listening to incoming
 * and outgoing web service calls to a WebCalendar service.
 * 
 * @author Craig Knudsen, craig@k5n.us
 * @version $Id$
 * 
 */
public interface WebCalendarClientListener {

  /**
   * The outgoingRequest method will be called before an outgoing request to a
   * WebCalendar server is sent.
   * 
   * @param text
   *          The outgoing request (a URL)
   */
  public void outgoingRequest ( String text );

  /**
   * The incomingResult method will be called after an incoming request from a
   * WebCalendar server is received.
   * 
   * @param text
   *          The incoming response text (XML)
   */
  public void incomingResult ( String text );

}
