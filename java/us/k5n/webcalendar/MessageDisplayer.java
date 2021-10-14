package us.k5n.webcalendar;

/**
 * Defines the API for displaying messages. Typically, a WebCalendar client
 * application will implement this interface and display popup windows for each
 * incoming message.
 *
 * @author Craig Knudsen
 */
public interface MessageDisplayer {

  public void showReminder ( Reminder reminder );

  public void showMessage ( String message );

  public void showError ( String message );

}
