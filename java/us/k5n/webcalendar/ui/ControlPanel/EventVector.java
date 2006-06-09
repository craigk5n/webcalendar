package us.k5n.webcalendar.ui.ControlPanel;

import java.util.Vector;

import us.k5n.webcalendar.Event;
import us.k5n.webcalendar.Participant;

public class EventVector extends Vector {
  private Event event;
  private Participant p;

  public EventVector ( Event e ) {
    super ();
    event = e;
  }

  public EventVector ( Event e, Participant p ) {
    super ();
    this.event = e;
    this.p = p;
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
        return p.getLogin ();
      case 3:
        return event.getName ();
      default:
        return "-";
    }
  }
  
  public String toString ()
  {
    return event == null ? "No event" : event.toString ();
  }

}
