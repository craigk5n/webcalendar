<?php
/* Class to utilize Smarty class 
 *
 * Smarty's homepage http://smarty.php.net/
 *
 * @author Ray Jones <rjones@umces.edu>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 * @subpackage Smarty
 */
require ( 'smarty/libs/Smarty.class.php' );

class WebCalSmarty extends Smarty {

  /* Constructor */
  function WebCalSmarty ( &$WC ) {
	
    $this->assign_by_ref('WC', $WC);
    $this->template_dir = 'templates';
		//the following link points to \cache\templates_c
    $this->compile_dir  = SMARTY_DIR  . '..\..\..\..\cache\templates_c';
    $this->config_dir   = SMARTY_DIR . '..\configs';
	  $this->compile_id   = $WC->lang();
		$this->config_load('wc.conf');
	  //init some common Smarty variables
	  $this->assign('eventinfo','' );
		$this->load_filter('output','trimwhitespace');
		
		//these plugins used within other smarty plugins and need to be loaded
    require_once $this->_get_plugin_filepath('modifier', 'date_to_str');
    require_once $this->_get_plugin_filepath('modifier', 'display_time');		
		
  }

  function LoadVars ( $user='', $boolean=true) {
		//load webcal_config values
		$s = loadConfig ( $boolean );
	  $this->assign('s', $s );	
		if ( ! empty ( $user ) ) {
		  //load user perferences
		  $prefarray = array_merge ( $s, loadPreferences ( $user, $boolean ) );
      $this->assign ( 'p', $prefarray);
		}	
	}
  /* Replace the default error handler so we can add our own trailer. */
  function SetError ( $msg ) {
    global $mailerError;
    $this->error_count++;
    $mailerError .= $msg . '<br />';
  }

 
}
/*
 The following comments will be picked up by update_translation.pl so
 translators will find them.
*/

?>
