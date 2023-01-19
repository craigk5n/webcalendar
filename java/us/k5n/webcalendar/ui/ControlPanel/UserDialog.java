package us.k5n.webcalendar.ui.ControlPanel;

import java.awt.BorderLayout;
import java.awt.GridLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

import javax.swing.BorderFactory;
import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JDialog;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JPasswordField;
import javax.swing.JTextField;

import us.k5n.webcalendar.User;
import us.k5n.webcalendar.WebCalendarClient;

/**
 * The UserDialog class provides a dialog window for editing or adding a user.
 *
 * @author Craig Knudsen
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
      UserListener userListenerIn ) {
    this ( clientIn, parent, mode, userListenerIn, null );
  }

  public UserDialog ( WebCalendarClient clientIn, JFrame parent, int modeIn,
      UserListener userListenerIn, User user ) {
    super ( parent, modeIn == EDIT_MODE ? "Edit User" : "Add User", true );
    this.client = clientIn;
    this.userListener = userListenerIn;
    mode = modeIn;
    // setSize ( 400, 400 );
    JPanel buttonPanel, topPanel;

    getContentPane ().setLayout ( new BorderLayout () );

    buttonPanel = new JPanel ();
    JButton okButton = new JButton ( "Ok" );
    buttonPanel.add ( okButton );
    getContentPane ().add ( buttonPanel, BorderLayout.SOUTH );
    okButton.addActionListener ( // Anonymous class as a listener.
        new ActionListener () {
          public void actionPerformed ( ActionEvent e ) {
            if (mode == ADD_MODE
                && !password1.getText ().equals ( password2.getText () )) {
              client.showError ( "Passwords do not match" );
            } else if (username.getText ().length () == 0) {
              client.showError ( "You must enter a username" );
            } else if (mode == ADD_MODE && password1.getText ().length () == 0) {
              client.showError ( "You must enter a password" );
            } else {
              if (mode == ADD_MODE && client.addUser ( getUser () )) {
                // success...
                userListener.updateUserList ();
                hide ();
                dispose ();
              } else if (mode == EDIT_MODE && client.updateUser ( getUser () )) {
                // success...
                userListener.updateUserList ();
                hide ();
                dispose ();
              }
            }
          }
        } );

    JButton cancelButton = new JButton ( "Cancel" );
    buttonPanel.add ( cancelButton );
    getContentPane ().add ( buttonPanel, BorderLayout.SOUTH );
    cancelButton.addActionListener ( // Anonymous class as a listener.
        new ActionListener () {
          public void actionPerformed ( ActionEvent e ) {
            hide ();
            dispose ();
          }
        } );

    topPanel = new JPanel ();
    topPanel.setLayout ( new GridLayout ( mode == ADD_MODE ? 7 : 5, 2 ) );
    topPanel.setBorder ( BorderFactory
        .createTitledBorder ( mode == ADD_MODE ? "Add User" : "Edit User" ) );

    topPanel.add ( new JLabel ( "User login: " ) );
    username = new JTextField ( 25 );
    username.setName ( "username" );
    if (mode == EDIT_MODE) {
      if (user != null && user.login != null)
        username.setText ( user.login );
      username.setEditable ( false );
    }
    topPanel.add ( username );

    topPanel.add ( new JLabel ( "First name: " ) );
    firstname = new JTextField ( 15 );
    firstname.setName ( "firstname" );
    if (mode == EDIT_MODE && user.firstName != null)
      firstname.setText ( user.firstName );
    topPanel.add ( firstname );

    topPanel.add ( new JLabel ( "Last name: " ) );
    lastname = new JTextField ( 15 );
    lastname.setName ( "lastname" );
    if (mode == EDIT_MODE && user.lastName != null)
      lastname.setText ( user.lastName );
    topPanel.add ( lastname );

    // WebCalendar uses firstname & lastname to derive
    // the display/full name
    /*
     * subP = new JPanel (); subP.setLayout ( new FlowLayout () ); subP.add (
     * new JLabel ( "Full name: " ) ); fullname = new JTextField ( 25 );
     * fullname.setName ( "fullname" ); subP.add ( fullname ); topPanel.add (
     * subP );
     */

    topPanel.add ( new JLabel ( "Email: " ) );
    email = new JTextField ( 25 );
    if (mode == EDIT_MODE && user.email != null)
      email.setText ( user.email );
    topPanel.add ( email );

    if (mode == ADD_MODE) {
      topPanel.add ( new JLabel ( "Password: " ) );
      password1 = new JPasswordField ( 25 );
      password1.setEchoChar ( '*' );
      topPanel.add ( password1 );

      topPanel.add ( new JLabel ( "Password (confirm): " ) );
      password2 = new JPasswordField ( 25 );
      password2.setEchoChar ( '*' );
      topPanel.add ( password2 );
    }

    java.util.Vector userTypeOptions = new java.util.Vector ();
    userTypeOptions.addElement ( "User" );
    userTypeOptions.addElement ( "Administrator" );

    topPanel.add ( new JLabel ( "User type: " ) );
    userType = new JComboBox ( userTypeOptions );
    topPanel.add ( userType );

    getContentPane ().add ( topPanel, BorderLayout.CENTER );

    pack ();
    show ();
  }

  public User getUser () {
    User u = new User ( username.getText () );
    u.firstName = firstname.getText ();
    u.lastName = lastname.getText ();
    // u.fullName = fullname.getText ();
    if (password1 != null)
      u.password = password1.getText ();
    else
      u.password = null;
    u.email = email.getText ();
    Object o = userType.getSelectedItem ();
    u.isAdmin = ( o != null && o.toString ().toLowerCase ().startsWith (
        "admin" ) );

    return u;
  }
}
