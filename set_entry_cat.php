<?php
/* $Id: set_entry_cat.php,v 1.40.2.7 2013/01/24 21:10:20 cknudsen Exp $
 *
 * Allows the setting of categories by each participant of an event.
 *
 * Multiple categories can be added by each participant and stored separately
 * for that user. Global categories will be visible by all participants,
 * but can only be added/removed by the owner or an admin in the edit-entry form.
 */
include_once 'includes/init.php';
load_user_categories ();

$error = '';

if ( empty ( $id ) )
  $error = translate ( 'Invalid entry id.' );
else
if ( $CATEGORIES_ENABLED != 'Y' )
  $error = print_not_auth (30);
else
if ( empty ( $categories ) )
  $error = translate ( 'You have not added any categories.' );

// Make sure user is a participant.
$res = dbi_execute ( 'SELECT cal_status FROM webcal_entry_user
  WHERE cal_id = ? AND cal_login = ?', array ( $id, $login ) );
if ( $res ) {
  if ( $row = dbi_fetch_row ( $res ) ) {
    if ( $row[0] == 'D' ) // User deleted themself.
      $error = print_not_auth (31);
  } else
    // Not a participant for this event.
    $error = print_not_auth (32);

  dbi_free_result ( $res );
} else
  $error = db_error ();

$cat_id = getValue ( 'cat_id', '-?[0-9,\-]*', true );
$cat_ids = $cat_name = array ();
$catNames = '';

// Get user's categories for this event.
$globals_found = false;
$categories = get_categories_by_id ( $id, $login, true );
if ( ! empty ( $categories ) ) {
  $catNames = htmlentities ( implode ( ', ', $categories ) );
  $keys = array_keys ( $categories );
  $catList = implode ( ',', $keys );
  sort ( $keys );
  if ( $keys[0] < 0 )
    $globals_found = true;
}

// Get event name and make sure event exists.
$event_name = '';
$res = dbi_execute ( 'SELECT cal_name FROM webcal_entry WHERE cal_id = ?',
  array ( $id ) );
if ( $res ) {
  if ( $row = dbi_fetch_row ( $res ) )
    $event_name = $row[0];
  else
    // No such event
    $error = translate ( 'Invalid entry id.' );

  dbi_free_result ( $res );
} else
  $error = db_error ();

// If this is the form handler, then save now
if ( ! empty ( $cat_id ) && empty ( $error ) ) {
  dbi_execute ( 'DELETE FROM webcal_entry_categories WHERE cal_id = ?
    AND ( cat_owner = ? )', array ( $id, $login ) );
  $categories = explode ( ',', $cat_id );

  $names = $sql_params = $values = array ();
  for ( $i = 0, $cnt = count ( $categories ); $i < $cnt; $i++ ) {
    // Don't process Global Categories.
    if ( $categories[$i] > 0 ) {
      $names[] = 'cal_id';
      $names[] = 'cat_id';
      $names[] = 'cat_order';
      $names[] = 'cat_owner';
      $values[] = '?';
      $values[] = '?';
      $values[] = '?';
      $values[] = '?';
      $sql_params[] = $id;
      $sql_params[] = abs ( $categories[$i] );
      $sql_params[] = ( $i + 1 );
      $sql_params[] = $login;
    }
  }

  if ( ! dbi_execute ( 'INSERT INTO webcal_entry_categories ( '
       . implode ( ', ', $names ) . ' ) VALUES ( '
       . implode ( ', ', $values ) . ' )', $sql_params ) )
    $error = db_error ();
  else
    do_redirect ( 'view_entry.php?id=' . $id
       . ( empty ( $date ) ? '' : '&amp;date=' . $date ) );
}

// Set up variables for inclusion later.
$setCatStr = translate ( 'Set Category' );
$briefStr = translate ( 'Brief Description' );
$catHelpStr = tooltip ( 'category-help' );
$catStr = translate ( 'Category' );
$editStr = translate ( 'Edit' );
$globalNoteStr = ( $globals_found
  ? translate ( 'Global Categories cannot be changed.' ) : '' );
$saveStr = translate ( 'Save' );

print_header ( array ( 'js/set_entry_cat.php/true' ) );

if ( ! empty ( $error ) )
  echo print_error ( $error );
else {
  echo <<<EOT
    <h2>{$setCatStr}</h2>
    <form action="set_entry_cat.php" method="post" name="selectcategory">
      <input type="hidden" name="date" value="{$date}" />
      <input type="hidden" name="id" value="{$id}" />
      <table border="0" cellpadding="5">
        <tr class="aligntop">
          <td class="bold">{$briefStr}:</td>
          <td>{$event_name}</td>
        </tr>
        <tr>
          <td class="tooltip" title="{$catHelpStr}" valign="top">
            <label for="entry_categories">{$catStr}:<br /></label>
            <input type="button" value="{$editStr}" onclick="editCats( event )" />
          </td>
          <td valign="top">
            <input readonly="readonly" type="text" name="catnames"
              value="{$catNames}" size="75" onclick="editCats( event )" /><br />
            {$globalNoteStr}
            <input type="hidden" name="cat_id" id="entry_categories"
              value="{$catList}" />
          </td>
        </tr>
        <tr class="aligntop">
          <td colspan="2"><br /><input type="submit" value="{$saveStr}" /></td>
        </tr>
      </table>
    </form>
EOT;
}
echo print_trailer ();

?>
