/*
 * $Id$
 *
 * Description:
 *	Event object
 *
 */

package us.k5n.webcalendar;

import java.util.Vector;
import java.util.Calendar;
import java.io.IOException;

// JAXP
import javax.xml.parsers.*;
// SAX
import org.xml.sax.*;
// DOM
import org.w3c.dom.*;

class siteExtra {
  public int number;
  public String name;
  public String description;
  public int type;
  public String value;
}

public class Event {
  /** Unique event id */
  public String id = null;
  /** Name of event */
  public String name = null;
  /** Full description of event */
  public String description = null;
  /** URL to view event in a browser */
  public String url = null;
  /** Date formatted to view in local timezone */
  String dateFormatted = null;
  /** Time formatted to view in local timezone */
  String timeFormatted = null;
  /** Date in YYYYMMDD format in server timezone */
  String date = null;
  /** Date as Calendar object */
  Calendar dateCalendar;
  /** Time in HHMM format in server timezone */
  String time = null;
  /** Duration of event (in minutes) */
  String duration = null;
  /** Priority of event */
  String priority = null;
  /** Access to event */
  String access = null;
  /** Username of creator of event */
  String createdBy = null;
  /** Date event was last updated */
  String updateDate = null;
  /** Time event was last updated */
  String updateTime = null;
  /** Vector of SiteExtra objects */
  Vector siteExtras = null;
  
  /**
    * Construct the reminder from the specified XML DOM node
    * (which corresponds to the <reminder> tag).
    */
  public Event ( Node eventNode ) throws WebCalendarParseException
  {
    NodeList list = eventNode.getChildNodes ();
    int len = list.getLength ();

    for ( int i = 0; i < len; i++ ) {
      Node n = list.item ( i );
    
      if ( n.getNodeType() == Node.ELEMENT_NODE ) {
        String nodeName = n.getNodeName ();
        if ( "name".equals ( nodeName ) ) {
          name = Utils.xmlNodeGetValue ( n );
        } else if ( "id".equals ( nodeName ) ) {
          id = Utils.xmlNodeGetValue ( n );
        } else if ( "description".equals ( nodeName ) ) {
          description = Utils.xmlNodeGetValue ( n );
        } else if ( "url".equals ( nodeName ) ) {
          url = Utils.xmlNodeGetValue ( n );
        } else if ( "dateFormatted".equals ( nodeName ) ) {
          dateFormatted = Utils.xmlNodeGetValue ( n );
        } else if ( "date".equals ( nodeName ) ) {
          date = Utils.xmlNodeGetValue ( n );
          dateCalendar = Utils.YYYYMMDDToCalendar ( date );
        } else if ( "time".equals ( nodeName ) ) {
          time = Utils.xmlNodeGetValue ( n );
        } else if ( "timeFormatted".equals ( nodeName ) ) {
          timeFormatted = Utils.xmlNodeGetValue ( n );
        } else if ( "duration".equals ( nodeName ) ) {
          duration = Utils.xmlNodeGetValue ( n );
        } else if ( "priority".equals ( nodeName ) ) {
          priority = Utils.xmlNodeGetValue ( n );
        } else if ( "access".equals ( nodeName ) ) {
          access = Utils.xmlNodeGetValue ( n );
        } else if ( "createdBy".equals ( nodeName ) ) {
          createdBy = Utils.xmlNodeGetValue ( n );
        } else if ( "updateDate".equals ( nodeName ) ) {
          updateDate = Utils.xmlNodeGetValue ( n );
        } else if ( "updateTime".equals ( nodeName ) ) {
          updateTime = Utils.xmlNodeGetValue ( n );
        } else if ( "siteExtras".equals ( nodeName ) ) {
          // NOT YET IMPLEMENTED
        } else if ( "participants".equals ( nodeName ) ) {
          // NOT YET IMPLEMENTED
        } else {
          System.err.println ( "Not sure what to do with <" + nodeName +
            "> tag (ignoring)" );
        }
      }
    }
  }

  /**
    * Does the event's date match the specified date?
    */
  public boolean dateMatches ( Calendar c )
  {
    if ( dateCalendar == null )
      return false;
    if ( dateCalendar.get ( Calendar.DAY_OF_MONTH ) !=
      c.get ( Calendar.DAY_OF_MONTH ) )
      return false;
    if ( dateCalendar.get ( Calendar.MONTH ) !=
      c.get ( Calendar.MONTH ) )
      return false;
    if ( dateCalendar.get ( Calendar.YEAR ) !=
      c.get ( Calendar.YEAR ) )
      return false;
    return true;
  }


  /**
    * Create a multiline String representation of this event.
    * This will include the event name, date and time.
    */
  public String toString()
  {
    StringBuffer sb = new StringBuffer ( 100 );
    if ( name != null ) {
      sb.append ( name );
      sb.append ( "\n" );
    }
    if ( description != null && 
      ( name == null || ! name.equals ( description ) ) ) {
      sb.append ( "Description: " );
      sb.append ( description );
      sb.append ( "\n" );
    }
    if ( dateFormatted != null ) {
      sb.append ( "Date: " );
      sb.append ( dateFormatted );
      sb.append ( "\n" );
    }
    if ( timeFormatted != null ) {
      sb.append ( "Time: " );
      sb.append ( timeFormatted );
      sb.append ( "\n" );
    }
    return sb.toString();
  }

}

