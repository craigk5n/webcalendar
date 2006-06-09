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
  private JScrollPane scrollPane;

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

    final JButton approveButton = new JButton ( "Approve" );
    cmdPanel.add ( approveButton );
    approveButton.addActionListener ( // Anonymous class as a listener.
        new ActionListener () {
          public void actionPerformed ( ActionEvent e ) {
            // Is a event selected?
            int[] sel = table.getSelectedRows ();
            if (sel.length == 0) {
              showError ( "You must select an event." );
            } else if (sel.length > 1) {
              showError ( "You must select\nonly one event." );
            } else {
              // one event selected... now confirm
              EventVector event = (EventVector)events.elementAt ( sel[0] );
              String[] options = { "Approve", "Cancel" };
              int n = JOptionPane.showOptionDialog (
                  approveButton.getParent (),
                  "Are you sure you want to\napprove the following event\n"
                      + "for user " + event.getParticipant ().getDisplayLogin ()
                      + "?\n\n" + event.toString (), "Confirm",
                  JOptionPane.YES_NO_OPTION, JOptionPane.QUESTION_MESSAGE,
                  null, options, options[1] );
              if (n == 0) {
                approveEvent ( event );
              }
            }
          }
        } );

    final JButton rejectButton = new JButton ( "Reject" );
    cmdPanel.add ( rejectButton );
    rejectButton.addActionListener ( // Anonymous class as a listener.
        new ActionListener () {
          public void actionPerformed ( ActionEvent e ) {
            // Is a event selected?
            int[] sel = table.getSelectedRows ();
            if (sel.length == 0) {
              showError ( "You must select an event." );
            } else if (sel.length > 1) {
              showError ( "You must select\nonly one event." );
            } else {
              // one event selected... now confirm
              EventVector event = (EventVector)events.elementAt ( sel[0] );
              String[] options = { "Reject", "Cancel" };
              int n = JOptionPane.showOptionDialog ( rejectButton.getParent (),
                  "Are you sure you want to\nreject the following event\n"
                      + "for user " + event.getParticipant ().getDisplayLogin ()
                      + "?\n\n" + event.toString (), "Confirm",
                  JOptionPane.YES_NO_OPTION, JOptionPane.QUESTION_MESSAGE,
                  null, options, options[1] );
              if (n == 0) {
                rejectEvent ( event );
              }
            }
          }
        } );

    final JButton deleteButton = new JButton ( "Delete" );
    cmdPanel.add ( deleteButton );
    deleteButton.addActionListener ( // Anonymous class as a listener.
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
              int n = JOptionPane.showOptionDialog ( deleteButton.getParent (),
                  "Are you sure you want to\ndelete the following event\n"
                      + "for user " + event.getParticipant ().getDisplayLogin ()
                      + "\n\n" + event.toString (), "Confirm",
                  JOptionPane.YES_NO_OPTION, JOptionPane.QUESTION_MESSAGE,
                  null, options, options[1] );
              if (n == 0) {
                deleteEvent ( event );
              }
            }
          }
        } );

    add ( cmdPanel, BorderLayout.SOUTH );

    scrollPane = new JScrollPane ();

    updateEventList ();

    table.setSelectionMode ( ListSelectionModel.SINGLE_SELECTION );

    add ( scrollPane, BorderLayout.CENTER );
  }

  private void showError ( String msg ) {
    JOptionPane.showMessageDialog ( this, msg, "Error",
        JOptionPane.WARNING_MESSAGE );
  }

  private void deleteEvent ( EventVector e ) {
    if (!client.deleteEvent ( e.getEvent (), e.getParticipant () ))
      showError ( "Error deleting event" );
    updateEventList ();
  }

  private void approveEvent ( EventVector e ) {
    if (!client.approveEvent ( e.getEvent (), e.getParticipant () ))
      showError ( "Error approving event" );
    updateEventList ();
  }

  private void rejectEvent ( EventVector e ) {
    if (!client.rejectEvent ( e.getEvent (), e.getParticipant () ))
      showError ( "Error rejecting event" );
    updateEventList ();
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
    if (table != null)
      scrollPane.remove ( table );
    if (events != null) {
      table = new ReadOnlyTable ( events, EventVector.getHeader () );
    } else {
      table = new ReadOnlyTable ( new Vector (), EventVector.getHeader () );
    }
    table.doLayout ();
    scrollPane.setViewportView ( table );
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
