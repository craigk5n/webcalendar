package us.k5n.webcalendar.ui.ControlPanel;

import java.awt.BorderLayout;
import java.awt.FlowLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.util.Vector;

import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JScrollPane;

import us.k5n.webcalendar.ActivityLogList;
import us.k5n.webcalendar.WebCalendarClient;

/**
 * The ActivityLogPanel creates the Activity Log JPanel display.
 * 
 * @author Craig Knudsen, craig@k5n.us
 * @version $Id$
 */
public class ActivityLogPanel extends JPanel {
  WebCalendarClient client;
  private ReadOnlyTable table = null;
  private JScrollPane scrollPane;
  private Vector colHeader = null;
  private ActivityLogList list = null;
  private int numToShow = 100;
  JComboBox numToShowCombo;

  public ActivityLogPanel ( WebCalendarClient client ) {
    super ();
    this.client = client;

    setLayout ( new BorderLayout () );

    JPanel choicePanel = new JPanel ();
    choicePanel.setLayout ( new FlowLayout ( FlowLayout.LEFT ) );
    choicePanel.add ( new JLabel ( "Entries to Display:" ) );
    String[] options = { "25", "50", "100", "250", "500", "1000" };
    numToShowCombo = new JComboBox ( options );
    choicePanel.add ( numToShowCombo, BorderLayout.CENTER );

    numToShowCombo.addActionListener ( // Anonymous class as a listener.
        new ActionListener () {
          public void actionPerformed ( ActionEvent e ) {
            updateActivtyLogTable ();
          }
        } );

    add ( choicePanel, BorderLayout.NORTH );

    JPanel cmdPanel = new JPanel ();
    cmdPanel.setLayout ( new FlowLayout () );

    JButton b = new JButton ( "Refresh" );
    cmdPanel.add ( b );
    b.addActionListener ( // Anonymous class as a listener.
        new ActionListener () {
          public void actionPerformed ( ActionEvent e ) {
            updateActivtyLogTable ();
          }
        } );

    /*
     * b = new JButton ( "Next Page" ); b.setEnabled ( false ); cmdPanel.add ( b );
     * b.addActionListener ( // Anonymous class as a listener. new
     * ActionListener () { public void actionPerformed ( ActionEvent e ) { //
     * TODO... } } );
     * 
     * b = new JButton ( "Previous Page" ); b.setEnabled ( false ); cmdPanel.add (
     * b ); b.addActionListener ( // Anonymous class as a listener. new
     * ActionListener () { public void actionPerformed ( ActionEvent e ) { //
     * TODO... } } );
     */

    add ( cmdPanel, BorderLayout.SOUTH );

    colHeader = new Vector ();
    colHeader.add ( "User" );
    colHeader.add ( "Calendar" );
    colHeader.add ( "Date" );
    colHeader.add ( "Action" );
    colHeader.add ( "Event" );
    scrollPane = new JScrollPane ();
    add ( scrollPane, BorderLayout.CENTER );

    updateActivtyLogTable ();
  }

  public void updateActivtyLogTable () {
    // Determine number to show
    String val = (String)numToShowCombo.getSelectedItem ();
    numToShow = Integer.parseInt ( val );
    try {
      String logList = client.query ( "ws/activity_log.php?num=" + numToShow );
      if (logList.indexOf ( "<activitylog>" ) < 0) {
        System.err.println ( "Invalid activity log XML:\n" + logList );
      } else {
        list = new ActivityLogList ( logList, "activitylog" );
      }
    } catch ( Exception e ) {
      System.err.println ( "Exception getting events: " + e );
      showError ( "Exception getting events:\n\n" + e.getMessage () );
      e.printStackTrace ();
    }
    if (table != null)
      scrollPane.remove ( table );
    if (list != null) {
      table = new ReadOnlyTable ( list, colHeader );
    } else {
      table = new ReadOnlyTable ( new Vector (), colHeader );
    }
    table.doLayout ();
    scrollPane.setViewportView ( table );
  }

  private void showError ( String msg ) {
    JOptionPane.showMessageDialog ( this, msg, "Error",
        JOptionPane.WARNING_MESSAGE );
  }

}
