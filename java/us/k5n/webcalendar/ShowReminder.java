package us.k5n.webcalendar;

/**
 * Interface for showing a reminders.
 *
 * @author Craig Knudsen
 */
public interface ShowReminder {

  public void showReminder ( Reminder reminder );

  public void showMessage ( String message );

  public void showError ( String message );

}
