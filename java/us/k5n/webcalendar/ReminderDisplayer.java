package us.k5n.webcalendar;

import java.util.Calendar;
import java.util.HashMap;

/**
 * ReminderDisplayer
 *
 * @author Craig Knudsen
 *
 */
public class ReminderDisplayer extends Thread {
  WebCalendarClient client;
  ReminderList reminders = null;
  HashMap done = null;
  int rescanSeconds = 15;

  public ReminderDisplayer ( WebCalendarClient client ) {
    super ();
    this.client = client;
    done = new HashMap ( 13 );
  }

  public synchronized void setReminders ( ReminderList list ) {
    this.reminders = list;
  }

  // loop through reminders in memory to see if any need to be displayed
  void scanReminders () {
    Calendar c = Calendar.getInstance ();
    // System.out.println ( "in sendReminders; c=" + c.getTime().toString() );
    for (int i = 0; reminders != null && i < reminders.size (); i++) {
      Reminder r = (Reminder)reminders.elementAt ( i );
      // System.out.println ( "id=" + r.event.id + ", cal=" +
      // r.remindCalendar.getTime().toString() );
      if (c.after ( r.remindCalendar ) && !done.containsKey ( r.event.id )) {
        client.getMessageDisplayer ().showReminder ( r );
        done.put ( r.event.id, r );
      }
    }
  }

  public void run () {
    while ( true ) {
      scanReminders ();
      try {
        Thread.currentThread ().sleep ( rescanSeconds * 1000 );
      } catch ( InterruptedException e ) {
        // The ReminderLoader class will interrupt us after it is done
        // loading all the reminders from the WebCalendar server.
      }
    }
  }
}
