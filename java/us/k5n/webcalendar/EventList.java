package us.k5n.webcalendar;

import java.io.IOException;
import java.io.StringBufferInputStream;
import java.util.Calendar;
import java.util.Vector;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;

import org.w3c.dom.Document;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;

/**
 * Contains a list of events.
 *
 * @author Craig Knudsen
 */
public class EventList extends Vector {
  Document document; // XML DOM object

  /**
   * Construct the list of events from the specified XML returned from the
   * WebCalendar server.
   *
   * @param xmlContent
   *          XML returned from WebCalendar server
   * @param tag
   *          The XML tag that contains all events
   */
  public EventList ( String xmlContent, String tag )
      throws WebCalendarParseException, WebCalendarErrorException {
    DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance ();
    try {
      DocumentBuilder builder = factory.newDocumentBuilder ();
      StringBufferInputStream is = new StringBufferInputStream ( xmlContent );
      document = builder.parse ( is );
      domToEvents ( document, tag );
    } catch ( SAXException sxe ) {
      // Error generated during parsing
      Exception x = sxe;
      if (sxe.getException () != null)
        x = sxe.getException ();
      x.printStackTrace ();
      System.err.println ( "XML:\n" + xmlContent + "\n[end xml]" );
      throw new WebCalendarParseException (
          "Error parsing XML from WebCalendar server: " + x.toString () );
    } catch ( IOException ioe ) {
      ioe.printStackTrace ();
      throw new WebCalendarParseException (
          "I/O Error parsing XML from WebCalendar server: " + ioe.toString () );
    } catch ( ParserConfigurationException pce ) {
      pce.printStackTrace ();
      throw new WebCalendarParseException (
          "Parser Config Error parsing XML from WebCalendar server: "
              + pce.toString () );
    }
  }

  /**
   * Create an empty list of events.
   */
  public EventList () {
    super ();
  }

  private void domToEvents ( Document document, String tag )
      throws WebCalendarParseException, WebCalendarErrorException {
    String error = Utils.getError ( document );
    if (error != null) {
      throw new WebCalendarErrorException ( error );
    }
    NodeList list = document.getElementsByTagName ( tag );
    if (list.getLength () < 1) {
      System.err.println ( "No <" + tag + "> found" );
      throw new WebCalendarParseException ( "No <" + tag + "> tag found in XML" );
    }
    if (list.getLength () > 1) {
      System.err.println ( "Too many <" + tag + "> found (" + list.getLength ()
          + ")" );
      throw new WebCalendarParseException ( "Too many <" + tag + "> found ("
          + list.getLength () + ")" );
    }
    Node eventsNode = list.item ( 0 );
    list = eventsNode.getChildNodes ();
    for (int i = 0; i < list.getLength (); i++) {
      Node n = list.item ( i );
      if (n.getNodeType () == Node.ELEMENT_NODE) {
        if ("event".equals ( n.getNodeName () )) {
          addElement ( new Event ( n ) );
        } else {
          System.err.println ( "Not sure what to do with <" + n.getNodeName ()
              + "> tag (expecting <event>... ignoring)" );
        }
      }
    }
  }

  /**
   * Get event at a specific location.
   */
  public Event eventAt ( int i ) {
    return (Event)elementAt ( i );
  }

  /**
   * Create a EventList for just the date specified.
   */
  public EventList getEventsForDate ( Calendar date ) {
    EventList ret = new EventList ();
    for (int i = 0; i < size (); i++) {
      Event e = eventAt ( i );
      if (e.dateMatches ( date ))
        ret.addElement ( e );
    }
    return ( ret );
  }

}
