package us.k5n.webcalendar.ui.ControlPanel;

import java.awt.BorderLayout;
import java.awt.FlowLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.io.IOException;
import java.io.StringBufferInputStream;
import java.util.HashMap;
import java.util.Vector;

import javax.swing.JButton;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.ListSelectionModel;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;

import org.w3c.dom.Document;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;

import us.k5n.webcalendar.Event;
import us.k5n.webcalendar.EventList;
import us.k5n.webcalendar.Participant;
import us.k5n.webcalendar.Utils;
import us.k5n.webcalendar.WebCalendarClient;
import us.k5n.webcalendar.WebCalendarErrorException;
import us.k5n.webcalendar.WebCalendarParseException;

/**
 * The UnapprovedEventsPanel is a JPanel for listing and handling (delete,
 * approve) those events. From here, users should be able to approve, delete or
 * reject events on any user's calendar that they have permission to do so. So,
 * if User Access Control is enabled, then the user may see events for multiple
 * users. If public access is enabled, then admin users should see events for
 * the public user.
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
                      + "for user "
                      + event.getParticipant ().getDisplayLogin () + "?\n\n"
                      + event.toString (), "Confirm",
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
                      + "for user "
                      + event.getParticipant ().getDisplayLogin () + "?\n\n"
                      + event.toString (), "Confirm",
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
                      + "for user "
                      + event.getParticipant ().getDisplayLogin () + "?\n\n"
                      + event.toString (), "Confirm",
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
      HashMap canApproveUsers = userListForApprove ( eventText, "userlist" );
      events = eventListToVector ( list, canApproveUsers );
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

  private Vector eventListToVector ( EventList list, HashMap users ) {
    Vector ret = new Vector ();
    for (int i = 0; i < list.size (); i++) {
      Event e = list.eventAt ( i );
      Vector participants = e.getParticipants ();
      for (int j = 0; j < participants.size (); j++) {
        Participant p = (Participant)participants.elementAt ( j );
        if (users.containsKey ( p.getLogin () )) {
          if (p.getStatus () != null && p.getStatus ().equalsIgnoreCase ( "W" )) {
            EventVector ev = new EventVector ( e, p );
            ret.addElement ( ev );
          }
        } else {
          System.out.println ( "Ignoring participant '" + p.getLogin ()
              + "' since this user cannot approve for them." );
        }
      }
    }
    return ret;
  }

  /**
   * Generate a HashMap of usernames that the current user is authorized to
   * approve/reject/delete for.
   * 
   * @param xmlContent
   *          XML returned from WebCalendar server
   * @param tag
   *          The XML tag that contains the list of users.
   */
  private HashMap userListForApprove ( String xmlContent, String tag )
      throws WebCalendarParseException, WebCalendarErrorException {
    DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance ();
    try {
      DocumentBuilder builder = factory.newDocumentBuilder ();
      StringBufferInputStream is = new StringBufferInputStream ( xmlContent );
      Document document = builder.parse ( is );
      return domToUserHashMap ( document, tag );
    } catch ( SAXException sxe ) {
      // Error generated during parsing
      Exception x = sxe;
      if (sxe.getException () != null)
        x = sxe.getException ();
      x.printStackTrace ();
      System.err.println ( "XML:\n" + xmlContent + "\n[end xml]" );
      throw new WebCalendarParseException (
          "Error parsing XML from WebCalendar server: " + x.toString () );
    } catch ( IOException ioe ) {
      ioe.printStackTrace ();
      throw new WebCalendarParseException (
          "I/O Error parsing XML from WebCalendar server: " + ioe.toString () );
    } catch ( ParserConfigurationException pce ) {
      pce.printStackTrace ();
      throw new WebCalendarParseException (
          "Parser Config Error parsing XML from WebCalendar server: "
              + pce.toString () );
    }
  }

  private HashMap domToUserHashMap ( Document document, String tag )
      throws WebCalendarParseException, WebCalendarErrorException {
    HashMap ret = new HashMap ();
    String error = Utils.getError ( document );
    if (error != null) {
      throw new WebCalendarErrorException ( error );
    }
    NodeList list = document.getElementsByTagName ( tag );
    if (list.getLength () < 1) {
      System.err.println ( "No <" + tag + "> found" );
      throw new WebCalendarParseException ( "No <" + tag + "> tag found in XML" );
    }
    if (list.getLength () > 1) {
      System.err.println ( "Too many <" + tag + "> found (" + list.getLength ()
          + ")" );
      throw new WebCalendarParseException ( "Too many <" + tag + "> found ("
          + list.getLength () + ")" );
    }
    Node usersNode = list.item ( 0 );
    list = usersNode.getChildNodes ();
    for (int i = 0; i < list.getLength (); i++) {
      Node n = list.item ( i );
      if (n.getNodeType () == Node.ELEMENT_NODE) {
        if ("login".equals ( n.getNodeName () )) {
          String login = Utils.xmlNodeGetValue ( n );
          ret.put ( login, login );
        } else {
          System.err.println ( "Not sure what to do with <" + n.getNodeName ()
              + "> tag (expecting <login>... ignoring) in <" + tag + ">" );
        }
      }
    }
    return ret;
  }

}
