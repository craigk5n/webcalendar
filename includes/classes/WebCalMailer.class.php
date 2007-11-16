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
$inc_path = ( ! empty ( $includedir ) ? $includedir : 'includes' );
if ( file_exists ( $inc_path . '/xcal.php' ) )
  include_once $inc_path . '/xcal.php'; // Used for ics attachments.
require ( 'phpmailer/class.phpmailer.php' );

class WebCalMailer extends phpmailer {
  var $WordWrap = 75;

  /* Constructor */
  function WebCalMailer () {
    global $mailerError;
    $mailerError = '';
    $this->Version .= ' extended by ' . generate_application_name ( false );
    $this->Host = getPref ( 'SMTP_HOST', 2 );
    $this->Mailer = getPref ( 'EMAIL_MAILER', 2 );
    $this->CharSet = translate ( 'charset' );
    // Turn on SMTP authentication.
    $this->SMTPAuth = ( getPref ( 'SMTP_AUTH', 2 ) ? true : false );
    $this->Username = getPref ( 'SMTP_USERNAME', 2 ); // SMTP username.
    $this->Password = getPref ( 'SMTP_PASSWORD', 2 ); // SMTP password.
  }

  /* Build email from single via single class call. */
  function WC_Send ( $from_name, $to_email,
    $to_name, $subject, $msg, $html = 'N', $from_email = '', $eid = '' ) {
    if ( strlen ( $from_email ) ) {
      $this->From = $from_email;
      $this->FromName = $from_name;
    } else
      $this->From = $from_name;

    $this->IsHTML ( $html == 'Y' ? true : false );
    $this->AddAddress ( $to_email, unhtmlentities ( $to_name, true ) );
    $this->WCSubject ( $subject );
    $this->Body ( $msg );
    if ( ! empty ( $eid ) )
      $this->IcsAttach ( $eid );
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
  function IcsAttach ( $eid ) {
    if ( function_exists ( 'export_ical' ) )
      $this->AddStringAttachment ( export_ical ( $eid, true ),
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
  function MailError ( $mailerError='', $error='' ) {
	  global $smarty;
		
		$errorStr = '';
    build_header ();
		if ( $mailerError ) 
		   $errorStr = '
         <h2>' . translate ( 'Email' ) . ' '
				 . translate ( 'Error' ) . '</h2>
         <blockquote>' . $mailerError . '</blockquote>'
		     . ( empty ( $error ) ? translate ( 'Changes successfully saved' ) : '' );
		else
		  $errorStr = $error ;

    $smarty->assign ( 'errorStr', $errorStr );
    $smarty->display ( 'error.tpl' );
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
