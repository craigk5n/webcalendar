/*
 * $Id$
 *
 * Description:
 *	WebCalendar Admin ControlPanel - used to administer WebCalendar
 *
 *	15-Sep-2005	cknudsen@cknudsen.com
 *			Created
 */

package us.k5n.webcalendar.ui.ControlPanel;

import java.util.Calendar;
import java.net.*;
import java.awt.*;
import java.awt.event.*;
import javax.swing.*;        
import us.k5n.webcalendar.*;


/**
  * Interface for listening for updates to the users.
  */
public interface UserListener
{

  /**
    * Notify the listener that the list of users has been modified
    * and needs to be reloaded.
    */
  public void updateUserList ();

}
