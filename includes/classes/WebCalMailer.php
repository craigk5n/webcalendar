<?php
/**
 * Class to over load PHPMailer class to utilize
 * WebCalendar's translation function.
 *
 * PHPMailer's homepage http://phpmailer.sourceforge.net/
 *
 * @author Ray Jones <rjones@umces.edu>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 * @subpackage Mailer
 */
$inc_path = ( defined( '__WC_INCLUDEDIR' ) ? __WC_INCLUDEDIR : 'includes' );

if( file_exists( $inc_path . '/xcal.php' ) )
  include_once $inc_path . '/xcal.php'; // Used for ics attachments.

require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

use phpmailer\PHPMailer;

class WebCalMailer {
  private $mail;

  /**
   * Constructor
   */
  function __construct() {
    global $EMAIL_MAILER, $mailerError,
    $SMTP_AUTH, $SMTP_HOST, $SMTP_PORT, $SMTP_PASSWORD, $SMTP_USERNAME;

    $this->mail = new PHPMailer\PHPMailer(false);
    $mailerError = '';
    $this->mail->Host = $SMTP_HOST;
    $this->mail->Port = $SMTP_PORT;
    #$this->mail->Mailer = $EMAIL_MAILER;
    $this->mail->isSMTP ();
    $this->mail->CharSet = translate( 'charset' );
    // Turn on SMTP authentication.
    $this->mail->SMTPAuth = ( $SMTP_AUTH == 'Y' );
    $this->mail->SMTPSecure = ( isset($SMTP_STARTTLS) && $SMTP_STARTTLS == 'Y' ) ? "tls" : "";
    $this->mail->SMTPDebug = 0;
    $this->mail->Username = $SMTP_USERNAME; // SMTP username.
    $this->mail->Password = $SMTP_PASSWORD; // SMTP password.
    //$this->mail->SMTPDebug = 4;
    // TODO: Support OAuth so we can use Gmail when 2FA is enabled.
  }

  /**
   * Build email from single via single class call.
   * Return true if mail was successfully sent.
   */
  function WC_Send($from_name, $to_email,
    $to_name, $subject, $msg, $html = 'N', $from_email = '', $id = '' ) {

    if( strlen( $from_email ) ) {
      $this->mail->SetFrom ( $from_email, $from_name );
      #$this->mail->From = $from_email;
      #$this->mail->FromName = $from_name;
    } else {
      $this->mail->SetFrom ( $from_email, $from_name );
      #$this->mail->From = $from_name;
    }

    $this->mail->IsHTML( $html == 'Y' );
    $this->mail->AddAddress( $to_email, unhtmlentities( $to_name, true ) );
    $this->WCSubject( $subject );
    $this->Body( $msg );

    if( ! empty( $id ) )
      $this->IcsAttach( $id );

    $ret = true;
    if ( ! $this->mail->Send() ) {
      # TODO: log this...
      #echo "Mail Error:\n" . $this->mail->ErrorInfo . "\n";
      #print_r ( $this->mail );
      $ret = false;
    }
    $this->ClearAll();
    return $ret;
  }

  /**
   * Replace the default language handler to use WebCalendar's function.
   */
  function Lang( $key ) {
    return translate( $key );
  }

  /**
   * Replace the default error handler so we can add our own trailer.
   */
  function SetError( $msg ) {
    global $mailerError;

    $this->error_count++;
    // $this->ErrorInfo = $msg;
    // die_miserable_death( $msg );
    $mailerError .= $msg . '<br />';
  }

  /**
   * Strip slashes from subject and pass thru unhtmlentities.
   */
  function WCSubject( $subject ) {
    $this->mail->Subject = unhtmlentities( generate_application_name( false ) . ' '
       . translate( 'Notification' ) . ': ' . stripslashes( $subject ) );
  }

  /**
   * Clean up msg as needed.
   */
  function Body( $msg ) {
    $msg = stripslashes( $msg );
    $this->mail->Body = ( $this->mail->ContentType == 'text/html'
      ? nl2br( $msg ) : unhtmlentities( $msg ) );
  }

  /**
   * Send ics file Attachment.
   */
  function IcsAttach( $id ) {
    if( function_exists( 'export_ical' ) )
      $this->mail->AddStringAttachment( export_ical( $id, true ),
        'WebCalendar.ics', 'base64', 'text/ical' );
  }

  /**
   * New function to clear ALL attributes.
   */
  function ClearAll() {
    $this->mail->ClearAddresses();
    $this->mail->ClearAllRecipients();
    $this->mail->ClearAttachments();
    $this->mail->ClearCustomHeaders();
  }

  /**
   * Locate common error function here.
   */
  function MailError( $mailerError, $error ) {
    print_header();
    echo ( ! empty( $mailerError ) ? '
    <h2>' . translate( 'Email' ) . ' ' . translate( 'Error' ) . '</h2>
    <blockquote>' . $mailerError
       . ( empty( $error ) ? translate( 'Changes successfully saved' ) : '' )
       . '</blockquote>'
      : print_error( $error ) )
     . print_trailer();
  }
}
/*
 The following comments will be picked up by update_translation.pl
 so translators will find them.
 translate( 'authenticate' ) translate( 'connect_host' )
 translate( 'data_not_accepted' ) translate( 'encoding' )
 translate( 'execute' ) translate( 'file_access' ) translate( 'file_open' )
 translate( 'from_failed' ) translate( 'instantiate' )
 translate( 'mailer_not_supported' ) translate( 'provide_address' )
 translate( 'recipients_failed' );
*/

?>
