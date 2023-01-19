package us.k5n.webcalendar;

import java.io.IOException;
import java.io.StringBufferInputStream;
import java.util.Vector;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;

import org.w3c.dom.Document;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;

/**
 * Contains a list of users.
 *
 * @author Craig Knudsen
 */
public class UserList extends Vector {
  Document document; // XML DOM object

  /**
   * Construct the list of users from the specified XML returned from the
   * WebCalendar server.
   *
   * @param xmlContent
   *          XML returned from WebCalendar server
   * @param tag
   *          The XML tag that contains all users
   */
  public UserList ( String xmlContent, String tag )
      throws WebCalendarParseException, WebCalendarErrorException {
    DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance ();
    try {
      DocumentBuilder builder = factory.newDocumentBuilder ();
      StringBufferInputStream is = new StringBufferInputStream ( xmlContent );
      document = builder.parse ( is );
      domToUsers ( document, tag );
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
   * Create an empty list of users.
   */
  public UserList () {
    super ();
  }

  private void domToUsers ( Document document, String tag )
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
    Node remindersNode = list.item ( 0 );
    list = remindersNode.getChildNodes ();
    for (int i = 0; i < list.getLength (); i++) {
      Node n = list.item ( i );
      if (n.getNodeType () == Node.ELEMENT_NODE) {
        if ("user".equals ( n.getNodeName () )) {
          addElement ( new User ( n ) );
        } else {
          System.err.println ( "Not sure what to do with <" + n.getNodeName ()
              + "> tag (expecting <user>... ignoring)" );
        }
      }
    }
  }

  /**
   * Get user at a specific location.
   */
  public User userAt ( int i ) {
    return (User)elementAt ( i );
  }

}
