<?php
/* Allows a user to specify a remote calendar by URL that can
 * be imported manually into the NUC calendar specified. The user
 * will also be allowed to create a layer to display this calendar 
 * on top of their own calendar.
 *
 * @author Ray Jones <rjones@umces.edu>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 * @subpackage Edit Remotes
 *
 * Security
 * _ENABLE_REMOTES must be enabled under System Settings and
 * the user must be allowed to ACCESS_IMPORT 
*/
include_once 'includes/init.php';
$INC = array('edit_remotes.js');
build_header ( $INC, '', '', 5 );

$error = '';

if ( ! getPref ( '_ENABLE_REMOTES', 2 )  || 
  ! access_can_access_function ( ACCESS_IMPORT ) ) {
  $error = print_not_auth ();
}

if ( $error ) {
  echo print_error ( $error );
  echo "</body>\n</html>";
  exit;
}
$nid = $WC->getValue ( 'nid' );
$smarty->assign ( 'nid', $nid );
$smarty->assign ( 'add', $WC->getValue ( 'add' ) );


if ( ! empty ( $nid ) ) {
  $nidData = $WC->User->loadVariables ( $nid, false );
	$smarty->assign ( 'rmt_login', $nidData['login'] );
	 $smarty->assign ( 'rmt_name', htmlspecialchars ( $nidData['fullname'] ) );
	 $smarty->assign ( 'rmt_url', htmlspecialchars ( $nidData['url'] ) );
}

$smarty->display ( 'edit_remotes.tpl' );

?>
