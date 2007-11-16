<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar translate function plugin
 *
 * Type:     function<br>
 * Name:     translate<br>
 * Purpose:  translate a text string into the required language
 * @author Ray Jones
 * @param string   $str 
 * @param mixed    $options  Whether to use unhtmlentities or padding
 *  Example:
 *  L2,R2 will add &nbsp;&nbsp; to each side of the returned value if needed
 *  T,P6 will use unhtmlentities and pad result to 6 characters
 * @return string
 */
function smarty_modifier_translate( $str, $options=false )
{
  if ( $options ) {
    $decode = ( $options === true || strpos( 'T', $options ) ? true : false );
	}
  $retval = translate( $str, $decode );
  //Pad result if requested
	if ( $options ) {
		$opts = explode ( ',', $options );
		foreach ( $opts as $opt ) {
	    if ( substr ( $opt,0,1 ) == 'L' )
			  $retval = str_repeat( '&nbsp;', substr ( $opt,1 ) ) . $retval;
			if ( substr ( $opt,0,1 ) == 'R' )
			  $retval .= str_repeat( '&nbsp;', substr ( $opt,1 ) );
		  if ( substr ( $opt,0,1 ) == 'P' ) {
			  $padstr = str_repeat( '&nbsp;', 
				  ( substr ( $opt,1 ) - (int) strlen ( $retval ) ) /2 );
			  $retval = $padstr . $retval .$padstr;		
			}
	  }
	}	
  return $retval;
    
}

/* vim: set expandtab: */

?>
