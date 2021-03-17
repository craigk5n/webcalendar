package us.k5n.webcalendar;

import java.util.Calendar;

import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

/**
 * The Reminder objects.
 *
 * @author Craig Knudsen
 */
public class Reminder {
  Event event = null;
  String untilRemind = null;
  String remindDate = null;
  String remindTime = null;
  Calendar remindCalendar; // date/time for reminder to be displayed

  /**
   * Construct the reminder from the specified XML DOM node (which corresponds
   * to the <reminder> tag).
   */
  public Reminder ( Node reminderNode ) throws WebCalendarParseException {
    NodeList list = reminderNode.getChildNodes ();
    int len = list.getLength ();

    for (int i = 0; i < len; i++) {
      Node n = list.item ( i );

      if (n.getNodeType () == Node.ELEMENT_NODE) {
        String nodeName = n.getNodeName ();
        if ("event".equals ( nodeName )) {
          event = new Event ( n );
        } else if ("remindDate".equals ( nodeName )) {
          remindDate = Utils.xmlNodeGetValue ( n );
        } else if ("remindTime".equals ( nodeName )) {
          remindTime = Utils.xmlNodeGetValue ( n );
        } else if ("untilRemind".equals ( nodeName )) {
          untilRemind = Utils.xmlNodeGetValue ( n );
        } else {
          System.err.println ( "Not sure what to do with <" + nodeName
              + "> tag (ignoring)" );
        }
      }
    }
    if (untilRemind != null) {
      remindCalendar = Calendar.getInstance ();
      int offset = Integer.parseInt ( untilRemind );
      remindCalendar.add ( Calendar.SECOND, offset );
    }
  }

  public String toString () {
    StringBuffer sb = new StringBuffer ( 100 );
    if (event != null && event.name != null) {
      sb.append ( event.toString () );
    }
    return sb.toString ();
  }

}
