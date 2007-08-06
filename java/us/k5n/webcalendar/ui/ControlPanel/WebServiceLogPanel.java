package us.k5n.webcalendar.ui.ControlPanel;

import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.FlowLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

import javax.swing.JButton;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTextPane;
import javax.swing.text.BadLocationException;
import javax.swing.text.Style;
import javax.swing.text.StyleConstants;
import javax.swing.text.StyleContext;
import javax.swing.text.StyledDocument;

/**
 * The WebServiceLogPanel is a JPanel for showing a log of all incoming and
 * outgoing web service traffic.
 * 
 * @author Craig Knudsen, craig@k5n.us
 * @version $Id$
 * 
 */
public class WebServiceLogPanel extends JPanel {
  JTextPane textPane;
  StyledDocument doc;

  public WebServiceLogPanel () {
    super ();

    setLayout ( new BorderLayout () );
    JPanel cmdPanel = new JPanel ();
    cmdPanel.setLayout ( new FlowLayout () );

    JButton b = new JButton ( "Clear" );
    cmdPanel.add ( b );
    b.addActionListener ( // Anonymous class as a listener.
        new ActionListener () {
          public void actionPerformed ( ActionEvent e ) {
            textPane.setText ( "" );
          }
        } );

    add ( cmdPanel, BorderLayout.SOUTH );

    textPane = new JTextPane ();
    doc = textPane.getStyledDocument ();
    addStylesToDocument ( doc );

    textPane.setEditable ( false );
    JScrollPane scrollPane = new JScrollPane ( textPane );

    add ( scrollPane, BorderLayout.CENTER );
  }

  protected void addStylesToDocument ( StyledDocument doc ) {
    // Initialize some styles.
    Style def = StyleContext.getDefaultStyleContext ().getStyle (
        StyleContext.DEFAULT_STYLE );

    Style s = doc.addStyle ( "italic", def );
    StyleConstants.setItalic ( s, true );

    s = doc.addStyle ( "request", def );
    StyleConstants.setBold ( s, true );
    StyleConstants.setForeground ( s, Color.RED );

    s = doc.addStyle ( "response", def );
    StyleConstants.setForeground ( s, Color.BLUE );
  }

  public void appendRequest ( String request ) {
    try {
      doc.insertString ( doc.getLength (), "\n\n" + request + "\n\n", doc
          .getStyle ( "request" ) );
    } catch ( BadLocationException e ) {
      System.err.println ( e.getMessage () );
      e.printStackTrace ();
    }
  }

  public void appendResposne ( String response ) {
    try {
      doc
          .insertString ( doc.getLength (), response, doc
              .getStyle ( "response" ) );
    } catch ( BadLocationException e ) {
      System.err.println ( e.getMessage () );
      e.printStackTrace ();
    }
  }

}
