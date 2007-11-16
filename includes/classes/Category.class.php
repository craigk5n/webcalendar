<?php
/**
 * This class provides a convenient way to load and access category information.
 *
 * @author Ray Jones
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 * @subpackage Category
 */


class Category {
  /**
   * The unique id
   * @var int
   * @access private
   */
  var $_cat_id;

  /**
   * Array of associated event ids (if any)
   * @var array
   * @access private
   */
  var $_event_id;

  /**
   * The user login of user who created this Category
   * @var string
   * @access private
   */
  var $_owner;

  /**
   * Color assigned to this category
   * @var string
   * @access private
   */
  var $_color;

  /**
   * The fimename of the icon assign to this category
   * @var string
   * @access private
   */
  var $_icon;

  function Category ()
  {
    return true;
  }
	
  /** Are categories enabled? */
  function categoriesEnabled ()
  {
    return ( getPref ( 'CATEGORIES_ENABLED', 2 ) );
  }

  /**
   * Gets the Doc's event id
   * @return int The Doc's event id
   * @access public
   */
  function getEventId () {
    return $this->_event_id;
  }

  /**
   * Gets the Doc creator's user login
   * @return string The Doc creator's user login
   * @access public
   */
  function getLogin () {
    return $this->_login;
  }

  /* Get categories for a given event id
   * Global categories are changed to negative numbers
   *
   * @param int      $eid  Id of event
   * @param string   $user normally this is $login
   * @param bool     $asterisk Include '*' if Global
   *
   * @return array   Array containing category names.
   */
  function get_categories_by_eid ( $eid, $user, $asterisk = false ) {
    global $WC;
  
    if ( empty ( $eid ) )
    return false;
  
    $categories = array ();
  
    $res = dbi_execute ( 'SELECT wc.cat_name, wc.cat_id, wc.cat_owner
    FROM webcal_categories wc, webcal_entry_categories wec WHERE wec.cal_id = ?
    AND wec.cat_id = wc.cat_id AND ( wc.cat_owner = ? OR wc.cat_owner IS NULL )
    ORDER BY wec.cat_order', array ( $eid, $WC->userLoginId() ) );
    while ( $row = dbi_fetch_row ( $res ) ) {
    $categories[ ( empty ( $row[2] ) ? - $row[1] : $row[1] ) ] = $row[0]
     . ( $asterisk && empty ( $row[2] ) ? '*' : '' );
    }
    dbi_free_result ( $res );
  
    return $categories;
  }
	
	function isMyCat ( $cid ) {
	
		// Does the category belong to the user?
		$is_my_cat = false;
		if ( empty ( $cid ) )
			$is_my_cat = true; // New event.
		else {
			$res = dbi_execute ( 'SELECT cat_id, cat_owner FROM webcal_categories
				WHERE cat_id = ?', array( $cid ) );
			if ( $res ) {
				$row = dbi_fetch_row ( $res );
	
				$is_my_cat = ( $row[0] == $cid && $WC->isLogin( $row[1] ) ||
					( empty ( $row[1] ) && $WC->isAdmin() ) );
	
				dbi_free_result ( $res );
			} else
				$error = db_error ();
			}
		  return $is_my_cat;
	}
	
	
	function delCat ( $cid ) {

    if ( ! $this->isMyCat ( $cid ) )
		  return false;
			
    if ( ! dbi_execute( 'DELETE FROM webcal_entry_categories 
		  WHERE cat_id = ?', array( $cid ) ) ){
      $error = db_error ();
    }
			
	  if ( ! dbi_execute( 'DELETE FROM webcal_categories
    WHERE cat_id = ? AND ( cat_owner = ?'
       . ( $WC->isAdmin() ? ' OR cat_owner IS NULL )' : ' )' ),
        array( $cid, $WC->loginId() ) ) ) {
    $error = db_error ();
    }
    return (empty ( $error ) );	
	}
	
	function updateCat ( $cid, $catname, $catcolor='', $caticon= '' ) {
	
	  // Update (don't let them change global status).
    if ( ! dbi_execute ( 'UPDATE webcal_categories
      SET cat_name = ?, cat_color = ?, cat_icon = ? WHERE cat_id = ?',
        array( $catname, $catcolor, $caticon, $cid ) ) )
      $error = db_error ();
		return (empty ( $error ) );	
	}
	
	
	/* Loads current user's category info and stuff it into category global variable.
 *
 * @param string $ex_global Don't include global categories ('' or '1')
 */
function loadCategories ( $ex_global = '' ) {
  global  $smarty, $WC, $is_assistant;

  $categories = array ();
  // These are default values.
  $categories[0]['cat_name'] = translate ( 'All' );
  $categories[-1]['cat_name'] = translate ( 'None' );
  if ( getPref ( 'CATEGORIES_ENABLED' ) ) {
    $query_params = array ();
    $query_params[] = ( $WC->_userId &&
      ( $is_assistant || $WC->isAdmin ) ? $WC->_userId : $WC->_loginId );
    $rows = dbi_get_cached_rows ( 'SELECT cat_id, cat_name, cat_owner, 
		  cat_color, cat_icon FROM webcal_categories 
			WHERE ( cat_owner = ? ) ' . ( $ex_global == ''
        ? 'OR ( cat_owner IS NULL ) ORDER BY cat_owner,' : 'ORDER BY' )
       . ' cat_name', $query_params );
    if ( $rows ) {
      for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
        $row = $rows[$i];
        $categories[$row[0]] = array (
          'cat_name' => $row[1],
          'cat_owner' => $row[2],
          'cat_color' => ( empty ( $row[3] ) ? '#000000' : $row[3] )
          );
      }
    }
	return $categories;
	$smarty->assign('categories', $categories );
  }
}
}
?>
