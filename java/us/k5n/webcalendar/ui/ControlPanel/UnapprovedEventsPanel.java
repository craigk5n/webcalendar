package us.k5n.webcalendar.ui.ControlPanel;

import java.awt.BorderLayout;
import java.awt.FlowLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.util.Vector;

import javax.swing.JButton;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.ListSelectionModel;

import us.k5n.webcalendar.Event;
import us.k5n.webcalendar.EventList;
import us.k5n.webcalendar.Participant;
import us.k5n.webcalendar.WebCalendarClient;

/**
 * The UnapprovedEventsPanel is a JPanel for listing and handling (delete,
 * approve) those events.
 * 
 * @author Craig Knudsen, craig@k5n.us
 * @version $Id$
 * 
 */
public class UnapprovedEventsPanel extends JPanel {
  ReadOnlyTable table;
  WebCalendarClient client;
  Vector events = null;

  public UnapprovedEventsPanel ( WebCalendarClient client ) {
    super ();

    this.client = client;

    setLayout ( new BorderLayout () );
    JPanel cmdPanel = new JPanel ();
    cmdPanel.setLayout ( new FlowLayout () );

    JButton b = new JButton ( "Refresh" );
    cmdPanel.add ( b );
    b.addActionListener ( // Anonymous class as a listener.
        new ActionListener () {
          public void actionPerformed ( ActionEvent e ) {
            updateEventList ();
          }
        } );

    b = new JButton ( "Approve" );
    cmdPanel.add ( b );
    b.addActionListener ( // Anonymous class as a listener.
        new ActionListener () {
          public void actionPerformed ( ActionEvent e ) {
            if (table.getSelectedRowCount () == 0) {
              showError ( "You must select an event to approver" );
            } else {
              // TODO: add confirm popup
              int[] sel = table.getSelectedRows ();
            }
          }
        } );

    final JButton b2 = new JButton ( "Delete" );
    cmdPanel.add ( b2 );
    b2.addActionListener ( // Anonymous class as a listener.
        new ActionListener () {
          public void actionPerformed ( ActionEvent e ) {
            // Is a event selected?
            int[] sel = table.getSelectedRows ();
            if (sel.length == 0) {
              showError ( "You must select at\nleast one event." );
            } else if (sel.length > 1) {
              showError ( "You must select\nonly one event." );
            } else {
              // one event selected... now confirm
              EventVector event = (EventVector)events.elementAt ( sel[0] );
              String[] options = { "Delete", "Cancel" };
              int n = JOptionPane.showOptionDialog ( b2.getParent (),
                  "Are you sure you want to\ndelete the following event?\n\n"
                      + event.toString (), "Confirm",
                  JOptionPane.YES_NO_OPTION, JOptionPane.QUESTION_MESSAGE,
                  null, options, options[1] );
              if (n == 0) {
                System.out.println ( "Deleting unapproved event" );
                deleteEvent ( event );
                updateEventList ();
              }
            }
          }
        } );

    add ( cmdPanel, BorderLayout.SOUTH );

    updateEventList ();

    if (events != null) {
      table = new ReadOnlyTable ( events, EventVector.getHeader () );
    } else {
      table = new ReadOnlyTable ( new Vector (), EventVector.getHeader () );
    }
    JScrollPane scrollPane = new JScrollPane ( table );

    // table.setCellRenderer ( new EventListCellRenderer () );
    table.setSelectionMode ( ListSelectionModel.SINGLE_SELECTION );

    add ( scrollPane, BorderLayout.CENTER );
  }

  private void showError ( String msg ) {
    JOptionPane.showMessageDialog ( this, "Error", msg,
        JOptionPane.WARNING_MESSAGE );
  }

  private void deleteEvent ( EventVector e ) {
    showError ( "Not yet implemented!" );
  }

  public void updateEventList () {
    try {
      String eventText = client.query ( "ws/get_unapproved.php" );
      EventList list = new EventList ( eventText, "events" );
      events = eventListToVector ( list );
    } catch ( Exception e ) {
      System.err.println ( "Exception getting events: " + e );
      showError ( "Exception getting events:\n\n" + e.getMessage () );
      e.printStackTrace ();
    }
  }

  private Vector eventListToVector ( EventList list ) {
    Vector ret = new Vector ();
    for (int i = 0; i < list.size (); i++) {
      Event e = list.eventAt ( i );
      Vector participants = e.getParticipants ();
      for (int j = 0; j < participants.size (); j++) {
        Participant p = (Participant)participants.elementAt ( j );
        if (p.getStatus () != null && p.getStatus ().equalsIgnoreCase ( "W" )) {
          EventVector ev = new EventVector ( e, p );
          ret.addElement ( ev );
        }
      }
    }
    return ret;
  }

}
