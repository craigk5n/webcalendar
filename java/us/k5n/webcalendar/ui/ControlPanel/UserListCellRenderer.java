/*
 * $Id$
 *
 * Description:
 *	Override the default JList cell renderer so we can use different
 *	colors in the JList to identify admin users.
 *	We could also add icons here if we wanted to...
 *
 * To-Do:
 *	Instead of using different colors, use different icons for admins
 *	and normal users.
 * History:
 *	$Log$
 *	Revision 1.1  2005/09/17 12:46:46  cknudsen
 *	Added support for deleting users.
 *	
 *
 */

package us.k5n.webcalendar.ui.ControlPanel;

import java.awt.*;
import java.awt.event.*;
import javax.swing.*;        
import us.k5n.webcalendar.*;


/**
  * User edit dialog window
  */
public class UserListCellRenderer extends JLabel
  implements ListCellRenderer {

  // This is the only method defined by ListCellRenderer.
  // We just reconfigure the JLabel each time we're called.

  public Component getListCellRendererComponent (
    JList list,
    Object value,            // value to display
    int index,               // cell index
    boolean isSelected,      // is the cell selected
    boolean cellHasFocus )   // the list and the cell have the focus
  {
    Color bg, fg;
    User user = (User) value;
    String s = value.toString();
    setText ( s );
    if ( isSelected ) {
      bg = list.getSelectionBackground ();
      fg = list.getSelectionForeground ();
      if ( user.isAdmin ) {
        fg = Color.WHITE;
        bg = Color.RED;
      }
    }
    else {
      bg = list.getBackground();
      fg = list.getForeground();
      if ( user.isAdmin ) {
        fg = Color.RED;
        bg = Color.WHITE;
      }
    }

    setBackground ( bg );
    setForeground ( fg );

    setEnabled ( list.isEnabled() );
    setFont ( list.getFont() );
    setOpaque ( true );
    return this;
  }

}


