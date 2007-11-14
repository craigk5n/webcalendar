<?php
/* Class to over load PHPMailer class to utilize
 * WebCalendar's translation function.
 *
 * PHPMailer's homepage http://phpmailer.sourceforge.net/
 *
 * @author Ray Jones <rjones@umces.edu>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 * @subpackage Mailer
 */
$inc_path = ( defined ( '__WC_INCLUDEDIR' ) ? __WC_INCLUDEDIR : 'includes' );
if ( file_exists ( $inc_path . '/xcal.php' ) )
  include_once $inc_path . '/xcal.php'; // Used for ics attachments.
require ( 'phpmailer/class.phpmailer.php' );

class WebCalMailer extends phpmailer {
  var $WordWrap = 75;

  /* Constructor */
  function WebCalMailer () {
    global $EMAIL_MAILER, $mailerError,
    $SMTP_AUTH, $SMTP_HOST, $SMTP_PASSWORD, $SMTP_USERNAME;
    $mailerError = '';
    $this->Version .= ' extended by ' . generate_application_name ( false );
    $this->Host = $SMTP_HOST;
    $this->Mailer = $EMAIL_MAILER;
    $this->CharSet = translate ( 'charset' );
    // Turn on SMTP authentication.
    $this->SMTPAuth = ( $SMTP_AUTH == 'Y' ? true : false );
    $this->Username = $SMTP_USERNAME; // SMTP username.
    $this->Password = $SMTP_PASSWORD; // SMTP password.
  }

  /* Build email from single via single class call. */
  function WC_Send ( $from_name, $to_email,
    $to_name, $subject, $msg, $html = 'N', $from_email = '', $id = '' ) {
    if ( strlen ( $from_email ) ) {
      $this->From = $from_email;
      $this->FromName = $from_name;
    } else
      $this->From = $from_name;

    $this->IsHTML ( $html == 'Y' ? true : false );
    $this->AddAddress ( $to_email, unhtmlentities ( $to_name, true ) );
    $this->WCSubject ( $subject );
    $this->Body ( $msg );
    if ( ! empty ( $id ) )
      $this->IcsAttach ( $id );
    $this->Send ();
    $this->ClearAll ();
  }

  /* Replace the default language handler to use WebCalendar's function. */
  function Lang ( $key ) {
    return translate ( $key );
  }

  /* Replace the default error handler so we can add our own trailer. */
  function SetError ( $msg ) {
    global $mailerError;
    $this->error_count++;
    // $this->ErrorInfo = $msg;
    // die_miserable_death ( $msg );
    $mailerError .= $msg . '<br />';
  }

  /* Strip slashes from subject and pass thru unhtmlentities. */
  function WCSubject ( $subject ) {
    $this->Subject = unhtmlentities ( generate_application_name ( false ) . ' '
       . translate ( 'Notification' ) . ': ' . stripslashes ( $subject ) );
  }

  /* Clean up msg as needed. */
  function Body ( $msg ) {
    $msg = stripslashes ( $msg );
    $this->Body = ( $this->ContentType == 'text/html'
      ? nl2br ( $msg ) : unhtmlentities ( $msg ) );
  }

  /* Send ics file Attachment. */
  function IcsAttach ( $id ) {
    if ( function_exists ( 'export_ical' ) )
      $this->AddStringAttachment ( export_ical ( $id, true ),
        'WebCalendar.ics', 'base64', 'text/ical' );
  }

  /* New function to clear ALL attributes. */
  function ClearAll () {
    $this->ClearAddresses ();
    $this->ClearAllRecipients ();
    $this->ClearAttachments ();
    $this->ClearCustomHeaders ();
  }

  /* Locate common error function here. */
  function MailError ( $mailerError, $error ) {
    print_header ();
    echo ( ! empty ( $mailerError ) ? '
    <h2>' . translate ( 'Email' ) . ' ' . translate ( 'Error' ) . '</h2>
    <blockquote>' . $mailerError
       . ( empty ( $error ) ? translate ( 'Changes successfully saved' ) : '' )
       . '</blockquote>'
      : print_error ( $error ) )
     . print_trailer ();
  }
}
/*
 The following comments will be picked up by update_translation.pl so
 translators will find them.
 translate ( 'authenticate' ) translate ( 'connect_host' )
 translate ( 'data_not_accepted' ) translate ( 'encoding' )
 translate ( 'execute' ) translate ( 'file_access' ) translate ( 'file_open' )
 translate ( 'from_failed' ) translate ( 'instantiate' )
 translate ( 'mailer_not_supported' ) translate ( 'provide_address' )
 translate ( 'recipients_failed' );
*/

?>
