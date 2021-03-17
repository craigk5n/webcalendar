package us.k5n.webcalendar;

import java.util.Calendar;
import java.util.Vector;

import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

/**
 * Represents a log entry in the activity log. We extend the Vector class
 * because the JTable swing component can easily make use of a Vector for a row.
 *
 * @author Craig Knudsen
 */
public class ActivityLog extends Vector {
  /** Unique login id */
  public String login = null;
  /** User calendar affected */
  public String calendar = null;
  /** Type of activity */
  public char type = '?';
  /** Date in YYYYMMDD format in server timezone */
  String date = null;
  /** Date as Calendar object */
  Calendar dateCalendar;
  /** Time (seconds since midnight) in server timezone */
  public String time = null;
  /** Text description of entry */
  public String action = null;
  /** Unique Id */
  public int id = -1;
  protected String dateTime = null;

  /**
   * Construct the specified XML DOM node (which corresponds to the <log> tag).
   */
  public ActivityLog ( Node userNode ) throws WebCalendarParseException {
    NodeList list = userNode.getChildNodes ();
    int len = list.getLength ();

    for (int i = 0; i < len; i++) {
      Node n = list.item ( i );

      if (n.getNodeType () == Node.ELEMENT_NODE) {
        String nodeName = n.getNodeName ();
        if ("login".equals ( nodeName )) {
          login = Utils.xmlNodeGetValue ( n );
        } else if ("id".equals ( nodeName )) {
          String idStr = Utils.xmlNodeGetValue ( n );
          try {
            id = Integer.parseInt ( idStr );
          } catch ( Exception e ) {
            System.err.println ( "[" + this.toString ()
                + "]Invalid value for id '" + idStr + "' (ignoring)" );
          }
        } else if ("action".equals ( nodeName )) {
          action = Utils.xmlNodeGetValue ( n );
        } else if ("calendar".equals ( nodeName )) {
          calendar = Utils.xmlNodeGetValue ( n );
        } else if ("type".equals ( nodeName )) {
          String typeStr = Utils.xmlNodeGetValue ( n );
          if (typeStr == null) {
            // ignore...
          } else if (typeStr.trim ().length () != 1) {
            System.err.println ( "[" + this.toString ()
                + "]Invalid value for type '" + typeStr + "' (ignoring)" );
          } else {
            type = typeStr.trim ().charAt ( 0 );
          }
        } else if ("date".equals ( nodeName )) {
          date = Utils.xmlNodeGetValue ( n );
          dateCalendar = Utils.YYYYMMDDToCalendar ( date );
        } else if ("time".equals ( nodeName )) {
          time = Utils.xmlNodeGetValue ( n );
          if (time != null) {
            time = time.trim ();
            for (int j = 0; j < time.length (); j++) {
              char ch = time.charAt ( j );
              if (ch < '0' || ch > '9') {
                time = null;
                System.err.println ( "[" + this.toString ()
                    + "] Invalid time '" + time + "' (ignoring)" );
                time = null;
                break;
              }
            }
          }
        } else {
          System.err.println ( "[" + this.toString ()
              + "]Not sure what to do with <" + nodeName + "> tag (ignoring)" );
        }
      }
    }
    dateTime = getDate () + ' ' + getTime ();
  }

  /**
   * Get the text of the entry's action
   *
   * @return the text description of the entry
   */
  public String getAction () {
    return ( action == null ? "" : action );
  }

  /**
   * Get the type of entry ("Event created", etc.)
   *
   * @return The String description of the action type
   */
  public String getType () {
    switch ( type ) {
      case 'C':
        return "Event created";
      case 'A':
        return "Event approved";
      case 'X':
        return "Event rejected";
      case 'U':
        return "Event updated";
      case 'D':
        return "Event deleted";
      case 'N':
        return "Notification sent";
      case 'R':
        return "Reminder sent";
      case 'F':
        return "New user via self-registration";
      case 'E':
        return "New user via email";
      default:
        return "Unknown type";
    }
  }

  /**
   * Get the formatted date (YYYY/MM/DD) of the entry
   *
   * @return the formatted date
   */
  // TODO: Format date properly
  public String getDate () {
    if (date == null)
      return ( "????/??/??" );
    return date.substring ( 0, 4 ) + "-" + date.substring ( 4, 6 ) + "-"
        + date.substring ( 6, 8 );
  }

  public int size () {
    return 5;
  }

  public Object elementAt ( int i ) {
    switch ( i ) {
      case 0:
        return getUser ();
      case 1:
        return getCalendarUser ();
      case 2:
        return dateTime;
      case 3:
        return getType ();
      case 4:
        return getAction ();
      default:
        return "-";
    }
  }

  /**
   * Return the login of the user that caused the action
   *
   * @return the login name of the user
   */
  public String getUser () {
    return login;
  }

  /**
   * Return the time of the entry formatted as a HH:MM String
   *
   * @return the formatted time
   */
  // TODO: use WebCalendar format preferences
  public String getTime () {
    StringBuffer sb = new StringBuffer ( 8 );
    int t = Integer.parseInt ( time );
    if (t > 3600) {
      int h = t / 3600;
      if (h < 10)
        sb.append ( '0' );
      sb.append ( h );
      t %= 3600;
    }
    sb.append ( ':' );
    int m = t / 60;
    if (m < 10)
      sb.append ( '0' );
    sb.append ( m );
    t %= 60;
    sb.append ( ':' );
    if (t < 10)
      sb.append ( '0' );
    sb.append ( t );
    return sb.toString ();
  }

  /**
   * Return the calendar user who's calendar was affected
   *
   * @return the login user of the affected user
   */
  public String getCalendarUser () {
    return ( calendar == null ? "n/a" : calendar );
  }

  /**
   * Create a multiline String representation of this user.
   */
  public String toString () {
    StringBuffer sb = new StringBuffer ( 100 );
    if (calendar != null) {
      sb.append ( calendar );
      if (login != null) {
        sb.append ( " (" );
        sb.append ( login );
        sb.append ( ")" );
      }
    } else if (login != null) {
      sb.append ( login );
    }
    if (action != null) {
      sb.append ( ": " );
      sb.append ( action );
    }
    return sb.toString ();
  }

}
