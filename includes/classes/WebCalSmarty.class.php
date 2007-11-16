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
 
if (!defined('SMARTY_DIR')) {
    define('SMARTY_DIR', dirname(__FILE__) . '/smarty/libs/' );
}
require ( SMARTY_DIR . 'Smarty.class.php' );

class WebCalSmarty extends Smarty {

  /* Constructor */
  function WebCalSmarty ( &$WC ) {
    $version = $this->_version;
    $this->_version = $version .  '*/ 
    defined ( \'_ISVALID\' ) or die ( \'You cannot access this file directly!\' ) 
    /*';
    $this->assign_by_ref('WC', $WC);
    $this->template_dir = 'templates';
    //the following link points to \cache\templates_c
    $this->compile_dir  = 'cache/templates_c';
    $this->config_dir   = 'includes/smarty';
    $this->plugins_dir  =  array( 'includes/smarty', 'plugins');
    $this->config_load('wc.conf');  
    $this->load_filter('output','trimwhitespace');
    //init some common Smarty variables
    $this->assign('eventinfo','' );  
    
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
    //Init some commonly used translations
    $this->assign('Previous', translate ( 'Previous' ) );
    $this->assign('Next', translate ( 'Next' ) );    
  }
  /* Replace the default error handler so we can add our own trailer. */
  function SetError ( $msg ) {
    global $mailerError;
    $this->error_count++;
    $mailerError .= $msg . '<br />';
  }
  /* Replace the default display function so we can call translate. */
  function display ( $tpl ) {
    $this->register_prefilter('template_translate');
    parent::display ( $tpl );
  }
  
 
}


?>
