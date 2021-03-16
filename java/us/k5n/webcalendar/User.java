/*
 * Description:
 *  User object
 *
 */

package us.k5n.webcalendar;

import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

/**
 * Defines a WebCalendar user.
 *
 * @author Craig Knudsen
 */
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
  public User ( String login ) {
    this.login = login;
  }

  /**
   * Construct the specified XML DOM node (which corresponds to the <reminder>
   * tag).
   */
  public User ( Node userNode ) throws WebCalendarParseException {
    NodeList list = userNode.getChildNodes ();
    int len = list.getLength ();

    for (int i = 0; i < len; i++) {
      Node n = list.item ( i );

      if (n.getNodeType () == Node.ELEMENT_NODE) {
        String nodeName = n.getNodeName ();
        if ("login".equals ( nodeName )) {
          login = Utils.xmlNodeGetValue ( n );
        } else if ("firstname".equals ( nodeName )) {
          firstName = Utils.xmlNodeGetValue ( n );
        } else if ("lastname".equals ( nodeName )) {
          lastName = Utils.xmlNodeGetValue ( n );
        } else if ("fullname".equals ( nodeName )) {
          fullName = Utils.xmlNodeGetValue ( n );
        } else if ("email".equals ( nodeName )) {
          email = Utils.xmlNodeGetValue ( n );
        } else if ("admin".equals ( nodeName )) {
          String x = Utils.xmlNodeGetValue ( n );
          x = x.toLowerCase ();
          if (x.startsWith ( "1" ) || x.startsWith ( "y" ))
            isAdmin = true;
        } else {
          System.err.println ( "[" + this.toString ()
              + "]Not sure what to do with <" + nodeName + "> tag (ignoring)" );
        }
      }
    }
  }

  /**
   * @return Returns the email.
   */
  public String getEmail () {
    return email;
  }

  /**
   * @return Returns the firstName.
   */
  public String getFirstName () {
    return firstName;
  }

  /**
   * @return Returns the fullName.
   */
  public String getFullName () {
    return fullName;
  }

  /**
   * @return Returns the isAdmin.
   */
  public boolean isAdmin () {
    return isAdmin;
  }

  /**
   * @return Returns the lastName.
   */
  public String getLastName () {
    return lastName;
  }

  /**
   * @return Returns the login.
   */
  public String getLogin () {
    return login;
  }

  /**
   * @return Returns the password.
   */
  public String getPassword () {
    return password;
  }

  /**
   * Create a multiline String representation of this user.
   */
  public String toString () {
    StringBuffer sb = new StringBuffer ( 100 );
    if (fullName != null) {
      sb.append ( fullName );
      if (login != null) {
        sb.append ( " (" );
        sb.append ( login );
        sb.append ( ")" );
      }
    } else if (login != null) {
      sb.append ( login );
    }
    if (email != null) {
      sb.append ( " <" );
      sb.append ( email );
      sb.append ( ">" );
    }
    return sb.toString ();
  }

}
