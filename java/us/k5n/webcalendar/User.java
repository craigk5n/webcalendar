/*
 * $Id$
 *
 * Description:
 *	User object
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

public class User {
  /** Unique login id */
  public String login = null;
  /** First name */
  public String firstName = null;
  /** Last name */
  public String lastName = null;
  /** Full name */
  public String fullName = null;
  /** Email address */
  public String email = null;
  /** Is admin user? */
  public boolean isAdmin = false;
  /** password */
  public String password = null;

  /**
    * Simple constructor
    */
  public User ( String login )
  {
    this.login = login;
  }
  
  /**
    * Construct the specified XML DOM node
    * (which corresponds to the <reminder> tag).
    */
  public User ( Node userNode ) throws WebCalendarParseException
  {
    NodeList list = userNode.getChildNodes ();
    int len = list.getLength ();

    for ( int i = 0; i < len; i++ ) {
      Node n = list.item ( i );
    
      if ( n.getNodeType() == Node.ELEMENT_NODE ) {
        String nodeName = n.getNodeName ();
        if ( "login".equals ( nodeName ) ) {
          login = Utils.xmlNodeGetValue ( n );
        } else if ( "firstname".equals ( nodeName ) ) {
          firstName = Utils.xmlNodeGetValue ( n );
        } else if ( "lastname".equals ( nodeName ) ) {
          lastName = Utils.xmlNodeGetValue ( n );
        } else if ( "fullname".equals ( nodeName ) ) {
          fullName = Utils.xmlNodeGetValue ( n );
        } else if ( "email".equals ( nodeName ) ) {
          email = Utils.xmlNodeGetValue ( n );
        } else {
          System.err.println ( "[" + this.toString() +
            "]Not sure what to do with <" + nodeName +
            "> tag (ignoring)" );
        }
      }
    }
  }

  /**
    * Create a multiline String representation of this user.
    */
  public String toString()
  {
    StringBuffer sb = new StringBuffer ( 100 );
    if ( fullName != null ) {
      sb.append ( fullName );
      if ( login != null ) {
        sb.append ( " (" );
        sb.append ( login );
        sb.append ( ")" );
      }
    }
    else if ( login != null ) {
      sb.append ( login );
    }
    if ( email != null ) {
      sb.append ( " <" );
      sb.append ( email );
      sb.append ( ">" );
    }
    return sb.toString();
  }

}

