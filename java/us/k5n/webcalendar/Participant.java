package us.k5n.webcalendar;

import org.w3c.dom.Node;

/**
 * The Participant class defines an Event participant
 * 
 * @see Event
 * @author Craig Knudsen
 * @version $Id$
 */
public class Participant {
  String login = null;
  String status = null;
  /** Login used by the public access calendar */
  public static String PUBLIC_LOGIN = "__public__";
  /** Name to display as the user for the public access calendar */
  public static String PUBLIC_NAME = "Public User";

  public Participant ( String login ) {
    this.login = login;
  }

  /**
   * Create a Participant from the specified XML node. Typical format is
   * &lt;participant status="A"&gt;fsmith&lt;/participant&gt;
   * 
   * @param userNode
   *          The XML node for the participant
   * @throws WebCalendarParseException
   */
  public Participant ( Node userNode ) throws WebCalendarParseException {
    this.login = Utils.xmlNodeGetValue ( userNode );
    this.status = Utils.xmlNodeGetAttribute ( userNode, "status" );
  }

  /**
   * @return Returns the login.
   */
  public String getLogin () {
    return login;
  }

  /**
   * @return Returns the login suitable for display.
   */
  public String getDisplayLogin () {
    if (PUBLIC_LOGIN.equals ( login ))
      return PUBLIC_NAME;
    return login;
  }

  /**
   * @return Returns the status.
   */
  public String getStatus () {
    return status;
  }

}
