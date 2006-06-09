package us.k5n.webcalendar.ui.ControlPanel;

import java.util.Vector;

import us.k5n.webcalendar.Event;
import us.k5n.webcalendar.Participant;

/**
 * The EventVector object is a wrapper around the Event and Participant classes
 * intended to be used as a row in a JTable (which gladly accepts Vector objects
 * for each row).
 * 
 * @author Craig Knudsen, craig@k5n.us
 * @version $Id$
 */
public class EventVector extends Vector {
  private Event event;
  private Participant participant;

  public EventVector ( Event e ) {
    super ();
    event = e;
  }

  public EventVector ( Event e, Participant p ) {
    super ();
    this.event = e;
    this.participant = p;
  }

  public void setEvent ( Event e ) {
    this.event = e;
  }

  public static Vector getHeader () {
    Vector ret = new Vector ();
    ret.addElement ( "Date" );
    ret.addElement ( "Time" );
    ret.addElement ( "User" );
    ret.addElement ( "Event Title" );
    return ret;
  }

  public Object elementAt ( int i ) {
    switch ( i ) {
      case 0:
        return event.getDateFormatted ();
      case 1:
        return event.getTimeFormatted ();
      case 2:
        return participant.getDisplayLogin ();
      case 3:
        return event.getName ();
      default:
        return "-";
    }
  }

  /**
   * @return Returns the participant.
   */
  public Participant getParticipant () {
    return participant;
  }

  /**
   * @param participant
   *          The participant to set.
   */
  public void setParticipant ( Participant participant ) {
    this.participant = participant;
  }

  /**
   * @return Returns the event.
   */
  public Event getEvent () {
    return event;
  }

  public String toString () {
    return event == null ? "No event" : event.toString ();
  }

}
