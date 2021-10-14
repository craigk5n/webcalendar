package us.k5n.webcalendar;

import java.util.Calendar;

import org.w3c.dom.Attr;
import org.w3c.dom.Document;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

/**
 * Common functions.
 *
 * @author Craig Knudsen
 */
public class Utils {

  // For tags such as <name>xxx</name>, get the "xxx" for the Node.
  public static String xmlNodeGetValue ( Node node ) {
    NodeList list = node.getChildNodes ();
    int len = list.getLength ();
    if (len > 1)
      System.err.println ( "  Error: length of node=" + len + " for tag <"
          + node.getNodeName () + ">" );
    for (int i = 0; i < len; i++) {
      Node n = list.item ( i );
      // System.out.println ( " " + i + "> name=" + n.getNodeName() + ", value="
      // +
      // n.getNodeValue () + ", type=" + n.getNodeType() );
      if (n.getNodeType () == Node.TEXT_NODE) {
        return ( n.getNodeValue () );
      }
    }
    return ( null ); // not found
  }

  /**
   * For tags such as <name attr="xxx" />, get the "xxx".
   */
  public static String xmlNodeGetAttribute ( Node node, String name ) {
    NamedNodeMap list = node.getAttributes ();
    if (list == null)
      return null;
    int len = list.getLength ();
    if (len == 0)
      return null;
    for (int i = 0; i < len; i++) {
      Node n = list.item ( i );
      // System.out.println ( " " + i + "> name=" + n.getNodeName() + ", value="
      // +
      // n.getNodeValue () + ", type=" + n.getNodeType() );
      if (n.getNodeType () == Node.ATTRIBUTE_NODE) {
        Attr attr = (Attr)n;
        if (name.equalsIgnoreCase ( attr.getName () )) {
          return attr.getValue ();
        }
      }
    }
    return ( null ); // not found
  }

  /**
   * Return any text found in an <error> tag in the document or null if not
   * found.
   */
  public static String getError ( Document doc ) {
    StringBuffer sb = new StringBuffer ();

    NodeList list = doc.getElementsByTagName ( "error" );
    if (list == null || list.getLength () == 0)
      return null;
    for (int i = 0; i < list.getLength (); i++) {
      Node n = list.item ( i );
      if (sb.length () > 0)
        sb.append ( '\n' );
      sb.append ( xmlNodeGetValue ( n ) );
    }
    return sb.toString ();
  }

  /**
   * Convert a java.util.Calendar object to a YYYYMMDD String.
   *
   * @param inDate
   *          Date to convert
   * @return The Date as a String in YYYYMMDD format
   */
  public static String CalendarToYYYYMMDD ( Calendar inDate ) {
    StringBuffer ret = new StringBuffer ( 8 );
    ret.append ( inDate.get ( Calendar.YEAR ) );
    if (inDate.get ( Calendar.MONTH ) + 1 < 10)
      ret.append ( '0' );
    ret.append ( ( inDate.get ( Calendar.MONTH ) + 1 ) );
    if (inDate.get ( Calendar.DAY_OF_MONTH ) < 10)
      ret.append ( '0' );
    ret.append ( inDate.get ( Calendar.DAY_OF_MONTH ) );
    return ret.toString ();
  }

  /**
   * Convert a YYYYMMDD String into a java.util.Calendar object.
   *
   * @param inDate
   *          Date to convert
   * @return The Date as a Calendar
   */
  public static Calendar YYYYMMDDToCalendar ( String inDate ) {
    Calendar ret = Calendar.getInstance ();
    int year = Integer.parseInt ( inDate.substring ( 0, 3 ) );
    int month = Integer.parseInt ( inDate.substring ( 4, 5 ) );
    int day = Integer.parseInt ( inDate.substring ( 6, 7 ) );
    ret.set ( year, month, day );
    return ret;
  }

  /**
   * Get the date for the first day of the week for the specified date.
   */
  public static Calendar startOfWeek ( Calendar cal, boolean sundayStartsWeek ) {
    int dow = sundayStartsWeek ? Calendar.SUNDAY : Calendar.MONDAY;
    Calendar ret = (Calendar)cal.clone ();
    while ( ret.get ( Calendar.DAY_OF_WEEK ) != dow ) {
      ret.add ( Calendar.DATE, -1 );
    }
    return ret;
  }

  /**
   * Get the date for the first day of the week for the specified date.
   */
  public static Calendar endOfWeek ( Calendar cal, boolean sundayStartsWeek ) {
    int dow = sundayStartsWeek ? Calendar.SATURDAY : Calendar.SUNDAY;
    Calendar ret = (Calendar)cal.clone ();
    while ( ret.get ( Calendar.DAY_OF_WEEK ) != dow ) {
      ret.add ( Calendar.DATE, 1 );
    }
    return ret;
  }

}
