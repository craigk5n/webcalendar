/*
 * $Id$
 *
 * Description:
 *	Handle the results of the login process (parse the XML).
 *
 */

package us.k5n.webcalendar;

import java.util.Vector;
import java.io.IOException;
import java.io.StringBufferInputStream;

// JAXP
import javax.xml.parsers.*;
// SAX
import org.xml.sax.*;
// DOM
import org.w3c.dom.*;

public class LoginSession extends Vector {
  String cookieName = null;
  String cookieValue = null;
  Document document; // XML DOM object

  /**
    * Construct from the specified XML
    * returned from the WebCalendar server.
    */
  public LoginSession ( String xmlContent )
    throws WebCalendarParseException, WebCalendarErrorException
  {
    DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance ();
    try {
      DocumentBuilder builder = factory.newDocumentBuilder ();
      StringBufferInputStream is = new StringBufferInputStream ( xmlContent );
      document = builder.parse ( is );
      domToSession ( document );
    } catch ( SAXException sxe ) {
      // Error generated during parsing
      Exception x = sxe;
      if ( sxe.getException() != null )
        x = sxe.getException ();
      x.printStackTrace ();
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

  private void domToSession ( Document document )
    throws WebCalendarParseException, WebCalendarErrorException
  {
    String error = Utils.getError ( document );
    if ( error != null ) {
      throw new WebCalendarErrorException ( error );
    }
    NodeList list = document.getElementsByTagName ( "login" );
    if ( list.getLength() != 1 ) {
      System.err.println ( "Wrong number of <login> tags found" );
      throw new WebCalendarParseException ( "Wrong number of <login> tags found" );
    }
    Node loginNode = list.item ( 0 );
    list = loginNode.getChildNodes ();
    for ( int i = 0; i < list.getLength(); i++ ) {
      Node n = list.item ( i );
      if ( n.getNodeType() == Node.ELEMENT_NODE ) {
        if ( "cookieName".equals ( n.getNodeName() ) ) {
          cookieName = Utils.xmlNodeGetValue ( n );
        } else if ( "cookieValue".equals ( n.getNodeName() ) ) {
          cookieValue = Utils.xmlNodeGetValue ( n );
        } else {
          System.err.println ( "Not sure what to do with <" +
            n.getNodeName() + "> tag (expecting <cookieName>... ignoring)" );
        }
      }
    }
  }
}

