/*
 * $Id$
 *
 * Description:
 *	WebCalendar Admin ControlPanel
 *	Dialog window to edit or add a user
 *
 * History:
 *	$Log$
 *	Revision 1.1  2005/09/16 13:27:03  cknudsen
 *	Moved ControlPanel to its own directory:
 *	  us/k5n/webcalendar/ui/ControlPanel
 *	Updated ControlPanel to support adding users.
 *	(Admin flag is not recognized yet though.)
 *	
 *
 * TODO:
 *	Replace the layout with GridBagLayout.
 */

package us.k5n.webcalendar.ui.ControlPanel;

import java.util.Calendar;
import java.util.Vector;
import java.net.*;
import java.awt.*;
import java.awt.event.*;
import javax.swing.*;        
import us.k5n.webcalendar.*;


/**
  * User edit dialog window
  */
public class UserDialog extends JDialog {
  WebCalendarClient client = null;
  JFrame parent = null;
  static final int EDIT_MODE = 1;
  static final int ADD_MODE = 2;
  int mode;
  JTextField username, firstname, lastname, fullname, email;
  JPasswordField password1, password2;
  JComboBox userType;
  UserListener userListener = null;

  public UserDialog ( WebCalendarClient clientIn, JFrame parent, int mode,
    UserListener userListenerIn )
  {
    super ( parent, mode == EDIT_MODE ? "Edit User" : "Add User",
      true );
    this.client = clientIn;
    this.userListener = userListenerIn;
    //setSize ( 400, 400 );
    JPanel buttonPanel, topPanel;

    getContentPane().setLayout ( new BorderLayout () );

    buttonPanel = new JPanel ();
    JButton okButton = new JButton ( "Ok" );
    buttonPanel.add ( okButton );
    getContentPane().add ( buttonPanel, BorderLayout.SOUTH );
    okButton.addActionListener ( // Anonymous class as a listener.
      new ActionListener () {
        public void actionPerformed ( ActionEvent e ) {
          if ( ! password1.getText().equals ( password2.getText() ) ) {
            client.showError ( "Passwords do not match" );
          }
          else if ( username.getText().length() == 0 ) {
            client.showError ( "You must enter a username" );
          }
          else if ( password1.getText().length() == 0 ) {
            client.showError ( "You must enter a password" );
          }
          else if ( client.addUser ( getUser() ) ) {
            // success...
            userListener.updateUserList ();
            hide ();
            dispose ();
          }
        }
      }
    );

    JButton cancelButton = new JButton ( "Cancel" );
    buttonPanel.add ( cancelButton );
    getContentPane().add ( buttonPanel, BorderLayout.SOUTH );
    cancelButton.addActionListener ( // Anonymous class as a listener.
      new ActionListener () {
        public void actionPerformed ( ActionEvent e ) {
          hide ();
          dispose ();
        }
      }
    );


    topPanel = new JPanel ();
    topPanel.setLayout ( new BoxLayout ( topPanel, BoxLayout.Y_AXIS ) );

    JPanel subP = new JPanel ();
    subP.setLayout ( new FlowLayout () );
    subP.add ( new JLabel ( "User login: " )  );
    username = new JTextField ( 25 );
    username.setName ( "username" );
    // if editing...
    //username.setEditable ( false );
    subP.add ( username );
    topPanel.add ( subP );

    subP = new JPanel ();
    subP.setLayout ( new FlowLayout () );
    subP.add ( new JLabel ( "First name: " ) );
    firstname = new JTextField ( 15 );
    firstname.setName ( "firstname" );
    subP.add ( firstname );
    topPanel.add ( subP );

    subP = new JPanel ();
    subP.setLayout ( new FlowLayout () );
    subP.add ( new JLabel ( "Last name: " ) );
    lastname = new JTextField ( 15 );
    lastname.setName ( "lastname" );
    subP.add ( lastname );
    topPanel.add ( subP );

    subP = new JPanel ();
    subP.setLayout ( new FlowLayout () );
    subP.add ( new JLabel ( "Full name: " ) );
    fullname = new JTextField ( 25 );
    fullname.setName ( "fullname" );
    subP.add ( fullname );
    topPanel.add ( subP );

    subP = new JPanel ();
    subP.setLayout ( new FlowLayout () );
    subP.add ( new JLabel ( "Email: " ) );
    email = new JTextField ( 25 );
    subP.add ( email );
    topPanel.add ( subP );

    subP = new JPanel ();
    subP.setLayout ( new FlowLayout () );
    subP.add ( new JLabel ( "Password: " ) );
    password1 = new JPasswordField ( 25 );
    password1.setEchoChar ( '*' );
    subP.add ( password1 );
    topPanel.add ( subP );

    subP = new JPanel ();
    subP.setLayout ( new FlowLayout () );
    subP.add ( new JLabel ( "Password (confirm): " ) );
    password2 = new JPasswordField ( 25 );
    password2.setEchoChar ( '*' );
    subP.add ( password2 );
    topPanel.add ( subP );

    java.util.Vector userTypeOptions = new java.util.Vector ();
    userTypeOptions.addElement ( "User" );
    userTypeOptions.addElement ( "Administrator" );

    subP = new JPanel ();
    subP.setLayout ( new FlowLayout () );
    subP.add ( new JLabel ( "User type: " ) );
    userType = new JComboBox ( userTypeOptions );
    subP.add ( userType );
    topPanel.add ( subP );

    getContentPane().add ( topPanel, BorderLayout.CENTER );

    pack ();
    show ();
  }

  public User getUser ()
  {
    User u = new User ( username.getText () );
    u.firstName = firstname.getText ();
    u.lastName = lastname.getText ();
    u.fullName = fullname.getText ();
    u.password = password1.getText ();
    u.email = email.getText ();
    u.isAdmin = false; // TODO

    return u;
  }
}

