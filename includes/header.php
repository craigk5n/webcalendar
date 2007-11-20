<?php
/* Prints the HTML header and opening HTML body tag.
 *
 * @param array  $includes     Array of additional files to include referenced
 *                             from the includes directory
 * @param string $HeadX        Data to be printed inside the head tag (meta,
 *                             script, etc)
 * @param string $BodyX        Data to be printed inside the Body tag (onload
 *                             for example)
 * @param bool   $control      Do not include selected items
 */
function build_header ( $includes='', $HeadX='', $BodyX='', $control=0 ) {
  global $browser, $charset, $WC, $smarty,
  $REQUEST_URI;

  $disableCustom = ( $control & 1  ? true : false );
  $disableStyle  = ( $control & 2  ? true : false );
  $disableRSS    = ( $control & 4  ? true : false );
  $disableAJAX   = ( $control & 8  ? true : false );
  $disableUTIL   = ( $control & 16 ? true : false );
  $disableMENU   = ( $control & 32 ? true : false );
  
  $smarty->assign ('disableStyle', $disableStyle );
  $smarty->assign ('disableAJAX', $disableAJAX );
  $smarty->assign ('disableUTIL', $disableUTIL );
    
  $lang = $ret = '';
  // Remember this view if the file is a view_x.php script.
  if ( ! strstr ( $REQUEST_URI, 'view_entry' ) )
    remember_this_view ( true );

  // Menu control
  $menu_enabled = ( ! $WC->friendly() && ! $disableCustom && ! $disableMENU );
  $appStr = generate_application_name ( true );
  $smarty->assign ('app_name', $appStr );
  $smarty->assign ('doctype', send_doctype ( $appStr ) );
  
  //Set CSS dynamic name
  generate_CSS();
  //$smarty->assign ( 'cachedCSS', 'default.css' );
  $smarty->assign ( 'cachedCSS', 
    ( _WC_SCRIPT != 'admin.php' && $WC->userLoginId() ?
     md5($WC->userLoginId()) : 'default' ) . '.css' );
  // Any other includes?
  if ( is_array ( $includes ) ) {
    foreach ( $includes as $inc ) {
      if ( substr ( $inc, -2 ) == 'js' )
        $smarty->append ('jsincludes', $inc ); 
      }
  }

  // Add custom header if enabled.
  if ( getPref ( 'CUSTOM_HEADER' ) && ! $disableCustom  )
    $smarty->assign ('header_template', load_template( 'H' ) );
  
  // Add custom script/stylesheet if enabled.
  if ( getPref ( 'CUSTOM_SCRIPT' ) && ! $disableStyle )
    $smarty->assign ('css_template', load_template( 'S' ) );
  
  // Add custom footer if enabled. (Used in footer.tpl)
  if ( getPref ( 'CUSTOM_TRAILER' ) && ! $disableStyle )
    $smarty->assign ('footer_template', load_template( 'T' ) );  
  
  // Add RSS feed if publishing is enabled.
  $smarty->assign ('rss_enabled', getPref ( '_ENABLE_RSS' ) && getPref ('ENABLE_USER_RSS' ) && ! $disableRSS);

  if ( $menu_enabled){
    include_once 'includes/menu.php';
  $smarty->assign ( 'logout', ! empty ( $WC->_logout_url ) 
    && $menuConfig['Login'] );
  $smarty->assign ('menuScript', $menuScript );
  $smarty->assign ('menu_above', $menuConfig['Above Custom Header'] );
  $smarty->assign ( 'menuName', ( $menuConfig['Login Fullname'] 
    ? $WC->fullName : $WC->login() ));
  $smarty->assign ('menu_enabled', $menu_enabled); 
  $smarty->assign ('menu_date_top', getPref ( 'MENU_DATE_TOP' ) );
    // Add menu function to onload string as needed.
    $BodyX = ( ! empty ( $BodyX ) ? substr ( $BodyX, 0, -1 ) : 'onload="' )
      . "cmDraw ( 'myMenuID', myMenu, 'hbr', cm);;\"";
  }


  $smarty->assign ( 'include_nav_links', true ); 
  $smarty->assign ( '_DEMO_MODE', getPref ( '_DEMO_MODE', 2 ) );  
  
  $smarty->assign('navFullname', ( ! _WC_SINGLE_USER ?
    $WC->getFullName( $WC->userLoginId() ) : '' ) );
  $smarty->assign('navAdmin', ( $WC->isNonuserAdmin() ? '-- ' 
    . translate ( 'Admin mode' ) . ' --' : '' ) );
  $smarty->assign('navAssistant', ( access_user_calendar ( 'assistant', $WC->userLoginId()) ? '-- ' 
    . translate ( 'Assistant mode' ) . ' --' : '' ) ); 
    
  $smarty->assign ('HeadX', $HeadX);
  $smarty->assign ('BodyX', $BodyX); 
  $smarty->assign ('direction',( translate ( 'direction' ) == 'rtl' 
    ? ' dir="rtl"' : '' ) );
}

/* This just sends the DOCTYPE used in a lot of places in the code.
 *
 * @param string  lang
 */
function send_doctype ( $doc_title = '' ) {
  $language = getPref ( 'LANGUAGE' );
  $lang = ( empty ( $language ) ? '' : languageToAbbrev ( $language ) );
  if ( empty ( $lang ) )
    $lang = 'en';
  $charset = ( empty ( $language ) ? 'iso-8859-1' : translate ( 'charset' ) );

  return '<?xml version="1.0" encoding="' . $charset . '"?' . '>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . $lang . '" lang="'
   . $lang . '">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=' . $charset
   . '" />' . ( empty ( $doc_title ) ? '' : '
    <title>' . $doc_title . '</title>' );
}
?>
