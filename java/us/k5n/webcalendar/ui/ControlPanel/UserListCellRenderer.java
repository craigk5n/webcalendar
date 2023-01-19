package us.k5n.webcalendar.ui.ControlPanel;

import java.awt.Color;
import java.awt.Component;

import javax.swing.JLabel;
import javax.swing.JList;
import javax.swing.ListCellRenderer;

import us.k5n.webcalendar.User;

/**
 * This ListCellRenderer colors admin users as red and all other users as white
 * in the JList. We could also add icons here if we wanted to...
 *
 * @author Craig Knudsen
 */
public class UserListCellRenderer extends JLabel implements ListCellRenderer {

  /**
   * This is the only method defined by ListCellRenderer. We just reconfigure
   * the JLabel each time we're called.
   */
  public Component getListCellRendererComponent ( JList list, Object value,
      int index, boolean isSelected, boolean cellHasFocus ) {
    Color bg, fg;
    User user = (User)value;
    String s = value.toString ();
    setText ( s );
    if (isSelected) {
      bg = list.getSelectionBackground ();
      fg = list.getSelectionForeground ();
      if (user.isAdmin) {
        fg = Color.WHITE;
        bg = Color.RED;
      }
    } else {
      bg = list.getBackground ();
      fg = list.getForeground ();
      if (user.isAdmin) {
        fg = Color.RED;
        bg = Color.WHITE;
      }
    }

    setBackground ( bg );
    setForeground ( fg );

    setEnabled ( list.isEnabled () );
    setFont ( list.getFont () );
    setOpaque ( true );
    return this;
  }

}
