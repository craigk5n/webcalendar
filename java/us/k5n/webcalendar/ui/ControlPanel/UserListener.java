package us.k5n.webcalendar.ui.ControlPanel;

/**
 * Interface for listening for updates to the users.
 *
 * @author Craig Knudsen
 */
public interface UserListener {

  /**
   * Notify the listener that the list of users has been modified and needs to
   * be reloaded.
   */
  public void updateUserList ();

}
