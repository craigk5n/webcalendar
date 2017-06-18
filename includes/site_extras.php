<?php
/* $Id: site_extras.php,v 1.23.2.2 2007/08/06 02:28:32 cknudsen Exp $
 *
 * Page Description:
 *  This file can be used to define extra information associated with a
 *  calender entry.
 *
 *  You may define extra fields of the following types:
 *    EXTRA_TEXT - will allow user to enter a line of text
 *    EXTRA_MULTILINETEXT - will allow user to enter multiple lines of text
 *    EXTRA_URL - will be displayed as a link
 *    EXTRA_DATE - will be presented with date pulldown menus when entering
 *    EXTRA_EMAIL - will be presented as a mailto URL
 *    EXTRA_USER - must be a calendar user name; will be presented
 *                 with a pulldown
 *    EXTRA_RADIO - will display 1-n radio controls
 *    EXTRA_SELECTLIST - allows a custom selection list. Can use
 *      this to specify a list of possible locations, etc.
 *    EXTRA_CHECKBOX - will display a checkbox control
 *
 * Comments:
 *  If you want to fully support using languages other than what
 *  you define below, you will need to add the 2nd field of the arrays
 *  below to the translation files.
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
