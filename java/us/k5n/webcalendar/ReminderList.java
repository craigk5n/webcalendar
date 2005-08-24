/*
 * $Id$
 *
 * Description:
 *	Contains the list of pending reminders.
 *
 */

package us.k5n.webcalendar;

import java.util.Vector;
import java.io.IOException;
import java.io.StringBufferInputStream;

// JAXP
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.FactoryConfigurationError;
import javax.xml.parsers.ParserConfigurationException;

// SAX
import org.xml.sax.InputSource;
import org.xml.sax.SAXException;
import org.xml.sax.SAXParseException;

// DOM
import org.w3c.dom.Document;
import org.w3c.dom.DOMException;
import org.w3c.dom.NodeList;
import org.w3c.dom.Node;
import org.w3c.dom.Attr;
import org.w3c.dom.NamedNodeMap;

public class ReminderList extends Vector {
  Document document; // XML DOM object

  /**
    * Construct the list of reminders from the specified XML
    * returned from the WebCalendar server.
    */
  public ReminderList ( String xmlContent )
    throws WebCalendarParseException, WebCalendarErrorException
  {
    DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance ();
    try {
      DocumentBuilder builder = factory.newDocumentBuilder ();
      StringBufferInputStream is = new StringBufferInputStream ( xmlContent );
      document = builder.parse ( is );
      domToReminders ( document );
    } catch ( SAXException sxe ) {
      // Error generated during parsing
      Exception x = sxe;
      if ( sxe.getException() != null )
        x = sxe.getException ();
      x.printStackTrace ();
      System.err.println ( "XML:\n" + xmlContent + "[end xml]" );
      throw new WebCalendarParseException (
        "Error parsing XML from WebCalendar server: " + x.toString() );
    } catch ( IOException ioe ) {
      ioe.printStackTrace ();
      throw new WebCalendarParseException (
        "I/O Error parsing XML from WebCalendar server: " + ioe.toString() );
    } catch ( ParserConfigurationException pce ) {
      pce.printStackTrace ();
      throw new WebCalendarParseException (
        "Parser Config Error parsing XML from WebCalendar server: " + pce.toString() );
    }
  }

  private void domToReminders ( Document document )
    throws WebCalendarParseException, WebCalendarErrorException
  {
    String error = Utils.getError ( document );
    if ( error != null ) {
      throw new WebCalendarErrorException ( error );
    }
    NodeList list = document.getElementsByTagName ( "reminders" );
    if ( list.getLength() < 1 ) {
      System.err.println ( "No <reminders> found" );
      throw new WebCalendarParseException ( "No <reminders> tag found in XML" );
    }
    if ( list.getLength() > 1 ) {
      System.err.println ( "Too many <reminders> found (" +
        list.getLength() + ")" );
      throw new WebCalendarParseException ( "Too many <reminders> found (" +
        list.getLength() + ")" );
    }
    Node remindersNode = list.item ( 0 );
    list = remindersNode.getChildNodes ();
    for ( int i = 0; i < list.getLength(); i++ ) {
      Node n = list.item ( i );
      if ( n.getNodeType() == Node.ELEMENT_NODE ) {
        if ( "reminder".equals ( n.getNodeName() ) ) {
          addElement ( new Reminder ( n ) );
        } else {
          System.err.println ( "Not sure what to do with <" +
            n.getNodeName() + "> tag (expecting <reminder>... ignoring)" );
        }
      }
    }
  }
}

