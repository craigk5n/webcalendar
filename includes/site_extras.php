<?php
/* $Id$
 *
 * Page Description:
 *	This file can be used to define extra information associated with a
 *	calender entry.
 *
 *	You may define extra fields of the following types:
 *	  EXTRA_TEXT - will allow user to enter a line of text
 *	  EXTRA_MULTILINETEXT - will allow user to enter multiple lines of text
 *	  EXTRA_URL - will be displayed as a link
 *	  EXTRA_DATE - will be presented with date pulldown menus when entering
 *	  EXTRA_EMAIL - will be presented as a mailto URL
 *	  EXTRA_USER - must be a calendar user name; will be presented
 *	               with a pulldown
 *	  EXTRA_RADIO - will display 1-n radio controls
 *	  EXTRA_SELECTLIST - allows a custom selection list.  Can use
 *	    this to specify a list of possible locations, etc.
 *	  EXTRA_CHECKBOX - will display a checkbox control
 *
 * Comments:
 *	If you want to fully support using languages other than what
 *	you define below, you will need to add the 2nd field of the arrays
 *	below to the translation files.
 *
 */
// define types
define ( 'EXTRA_TEXT', 1 );
define ( 'EXTRA_MULTILINETEXT', 2 );
define ( 'EXTRA_URL', 3 );
define ( 'EXTRA_DATE', 4 );
define ( 'EXTRA_EMAIL', 5 );
define ( 'EXTRA_USER', 6 );
define ( 'EXTRA_RADIO', 7 );
define ( 'EXTRA_SELECTLIST', 8 );
define ( 'EXTRA_CHECKBOX', 9 );

//define view settings
define ( 'EXTRA_DISPLAY_POPUP', 1 );
define ( 'EXTRA_DISPLAY_VIEW', 2 );
define ( 'EXTRA_DISPLAY_EMAIL', 4 );
define ( 'EXTRA_DISPLAY_REMINDER', 8 );
define ( 'EXTRA_DISPLAY_REPORT', 16 );
define ( 'EXTRA_DISPLAY_WS', 32 );
define ( 'EXTRA_DISPLAY_ALL', 511 );

/* ****************************************************************************
 *                       Functions to handle site_extras                      *
 **************************************************************************** */

/* Formats site_extras for display according to their type.
 *
 * This will return an array containing formatted extras indexed on their
 * unique names.  Each formatted extra is another array containing two
 * indices: 'name' and 'data', which hold the name of the site_extra and the
 * formatted data, respectively.  So, to access the name and data of an extra
 * uniquely name 'Reminder', you would access
 * <var>$array['Reminder']['name']</var> and
 * <var>$array['Reminder']['data']</var>
 *
 * @param array $extras  Array of site_extras for an event as returned by
 *                       {@link get_site_extra_fields ()}
 * @param int   $filter  CONSTANT 'view settings' values from site_extras.php
 *
 * @return array  Array of formatted extras.
 */
function format_site_extras ( $extras, $filter = '' ) {
  global $site_extras;

  if ( empty ( $site_extras ) || empty ( $extras ) )
    return;

  $ret = array ();
  $extra_view = 1;
  foreach ( $site_extras as $site_extra ) {
    $data = '';
    $extra_name = $site_extra[0];
    $extra_desc = $site_extra[1];
    $extra_type = $site_extra[2];
    $extra_arg1 = $site_extra[3];
    $extra_arg2 = $site_extra[4];
    if ( ! empty ( $site_extra[5] ) && ! empty ( $filter ) )
      $extra_view = $site_extra[5] & $filter;
    if ( ! empty ( $extras[$extra_name] ) && !
        empty ( $extras[$extra_name]['cal_name'] ) && ! empty ( $extra_view ) ) {
      $name = translate ( $extra_desc );

      if ( $extra_type == EXTRA_DATE ) {
        if ( $extras[$extra_name]['cal_date'] > 0 )
          $data = date_to_str ( $extras[$extra_name]['cal_date'] );
      } elseif ( $extra_type == EXTRA_TEXT || $extra_type == EXTRA_MULTILINETEXT )
        $data = nl2br ( $extras[$extra_name]['cal_data'] );
      elseif ( $extra_type == EXTRA_RADIO && !
        empty ( $extra_arg1[$extras[$extra_name]['cal_data']] ) )
        $data .= $extra_arg1[$extras[$extra_name]['cal_data']];
      else
        $data .= $extras[$extra_name]['cal_data'];

      $ret[$extra_name] = array ( 'name' => $name, 'data' => $data );
    }
  }
  return $ret;
}

/* Gets any site-specific fields for an entry that are stored in the database
 * in the webcal_site_extras table.
 *
 * @param int $eventid  Event ID
 *
 * @return array  Array with the keys as follows:
 *   - <var>cal_name</var>
 *   - <var>cal_type</var>
 *   - <var>cal_date</var>
 *   - <var>cal_data</var>
 */
function get_site_extra_fields ( $eventid ) {
  $rows = dbi_get_cached_rows ( 'SELECT cal_name, cal_type, cal_date,
    cal_data FROM webcal_site_extras WHERE cal_id = ?', array ( $eventid ) );
  $extras = array ();
  if ( $rows ) {
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      // Save by cal_name (e.g. "URL").
      $extras[$row[0]] = array (
        'cal_name' => $row[0],
        'cal_type' => $row[1],
        'cal_date' => $row[2],
        'cal_data' => $row[3]
        );
    }
  }
  return $extras;
}

/* Extract the names of all site_extras.
 *
 * @param int $filter  CONSTANT 'view setting' from site_extras.php
 *
 * @return array  Array of site_extras names.
 */
function get_site_extras_names ( $filter = '' ) {
  global $site_extras;

  $ret = array ();

  foreach ( $site_extras as $extra ) {
    if ( $extra == 'FIELDSET' ||
      ( ! empty ( $extra[5] ) && ! empty ( $filter ) && !
          ( $extra[5] & $filter ) ) )
      continue;

    $ret[] = $extra[0];
  }

  return $ret;
}

/* Generates the HTML used in an event popup for the site_extras fields.
 *
 * @param int $eid  Event ID
 *
 * @return string  The HTML to be used within the event popup for any site_extra
 *                 fields found for the specified event.
 */
function site_extras_for_popup ( $eid ) {

  if ( ! getPref ( 'SITE_EXTRAS_IN_POPUP' ) )
    return '';

  $extras = format_site_extras ( get_site_extra_fields ( $eid ), EXTRA_DISPLAY_POPUP );
  if ( empty ( $extras ) )
    return '';

  $ret = '';

  foreach ( $extras as $extra ) {
    $ret .= '<dt>' . $extra['name'] . ":</dt>\n<dd>" . $extra['data'] . "</dd>\n";
  }

  return $ret;
}
//***DO NOT EDIT ABOVE THIS LINE***

/*
 Format of an entry is an array with the following elements:
   name:        unique name of this extra field (used in db).
                If 'FIELDSET' is specified, then a <fieldset> will be placed
                around all site_extra fields.
   description: how this field will be described to users
   type:        EXTRA_URL, EXTRA_TEXT, etc...
   arg1:        for multi-line text, how many columns to display in the form as
                in <textarea rows="XX" cols="XX".
                for text (single line), how many columns to display as in
                <input size="XX".
                for url, the href target attribute.
                for selection list, contains an array of possible values
   arg2:        for multi-line text, how many rows to display in the form as in
                <textarea rows="XX" cols="XX".
                for selection list, any number greater than zero makes the list
                multi-select and the size will min ( arg2, count ( arg1) ).
                for radio, this is the default array index to select.
                for checkbox, this is the fault state of the checkbox
   view setting:specifies the viewing permission of each site_extra element.
         EXTRA_DISPLAY_ALL = display in all cases
         EXTRA_DISPLAY_POPUP = display only in popups
         EXTRA_DISPLAY_ALL & ~ EXTRA_DISPLAY_POPUP = display all except popups
         EXTRA_DISPLAY_POPUP | EXTRA_DISPLAY_VIEW = display in popups OR view_entry
 Example 1:
   You want to add an URL, a reminder, an email address, an event contact
   (from list of calendar users), and some driving directions.

 $site_extras = array (
   'FIELDSET',     // Special case to display Fieldset in edit_entry.php
                   // If used, it must the first entry in $site_extras array
   array (
     "URL",        // unique name of this extra field (used in db)
     "Event URL",  // how this field will be described to users
     EXTRA_URL,    // type of field
     '_blank',     // href target of URL ( '', '_blank', '_top, etc )
     0,             // arg 2
     EXTRA_DISPLAY_ALL //Display in all places
   ),
   array (
     "Email",         // unique name of this extra field (used in db)
     "Event Email",   // how this field will be described to users
     EXTRA_EMAIL,     // type of field
     0,               // arg 1 (unused)
     0,                // arg 2 (unused)
     EXTRA_DISPLAY_ALL //Display in all places
   ),
   array (
     "Contact",       // unique name of this extra field (used in db)
     "Event Contact", // how this field will be described to users
     EXTRA_USER,      // type of field
     0,               // arg 1 (unused)
     0,                // arg 2 (unused)
     EXTRA_DISPLAY_ALL //Display in all places
   ),
   array (
     "Directions",         // unique name of this extra field (used in db)
     "Driving Directions", // how this field will be described to users
     EXTRA_MULTILINETEXT,  // type of field
     50,                   // width of text entry
     8,                    // height of text entry
     EXTRA_DISPLAY_ALL   //Display in all places

   ),
   array (
     "UserDepartment", // unique name of this extra field (used in db)
     "Department",     // how this field will be described to users
     EXTRA_RADIO,      // type of field
                       // List of options val->disp pair required for each option
     array ( 'HR'=>'Human Resources', 'PR'=>'Purchasing', 'IT'=>'IT Services'  ),
     'IT',             // default item
     EXTRA_DISPLAY_VIEW //Display in view_entry only
   ),
   array (
     "RoomLocation",    // unique name of this extra field (used in db)
     "Location",        // how this field will be described to users
     EXTRA_SELECTLIST,  // type of field
                        // List of options (first will be default)
     array ( "None", "Room 101", "Room 102", "Conf Room 8", "Conf Room 12" ),
     12,                // 0=single  >1=multiple && also the maximum size
                        // <select name="RoomLocation" multiple="multiple" size="12">
     EXTRA_DISPLAY_ALL //Display in all places
   ),
   array (
     "NeedLunch",       // unique name of this extra field (used in db)
     "Lunch",           // how this field will be described to users
     EXTRA_CHECKBOX,    // type of field
     'Y',               // Value of checkbox
     'Y',               // default state (set to above value to check )
     EXTRA_DISPLAY_POPUP | EXTRA_DISPLAY_VIEW //Display in Popups and View
   )
 );

 END EXAMPLES

 Define your stuff here...
 Below translate calls are here so they get picked up by update_translation.pl.
 They are never executed in PHP.
 Make sure you add translations in the translations file for anything
 you need to translate to another language.
 Use tools/check_translation.pl to verify you have all your translations.
*/
$site_extras = array (
);
?>
