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
 *	  EXTRA_USER - most be a calendar user name; will be presented
 *	               with a pulldown
 *	  EXTRA_SELECTION_LIST - allows a custom selection list.  Can use
 *	    this to specify a list of possible locations, etc.
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

define ( 'EXTRA_SELECTLIST', 8 );

/*
 Format of an entry is an array with the following elements:
   name:        unique name of this extra field (used in db)
   description: how this field will be described to users
   type:        EXTRA_URL, EXTRA_TEXT, etc...
   arg1:        for multi-line text, how many columns to display in the form as
                in <textarea rows="XX" cols="XX".
                for text (single line), how many columns to display as in
                <input size="XX".
                for selection list, contains an array of possible values
   arg2:        for multi-line text, how many rows to display in the form as in
                <textarea rows="XX" cols="XX"

 Example 1:
   You want to add an URL, a reminder, an email address, an event contact
   (from list of calendar users), and some driving directions.

 $site_extras = array (
   array (
     "URL",        // unique name of this extra field (used in db)
     "Event URL",  // how this field will be described to users
     EXTRA_URL,    // type of field
     0,            // arg 1
     0             // arg 2
   ),
   array (
     "Email",         // unique name of this extra field (used in db)
     "Event Email",   // how this field will be described to users
     EXTRA_EMAIL,     // type of field
     0,               // arg 1 (unused)
     0                // arg 2 (unused)
   ),
   array (
     "Contact",       // unique name of this extra field (used in db)
     "Event Contact", // how this field will be described to users
     EXTRA_USER,      // type of field
     0,               // arg 1 (unused)
     0                // arg 2 (unused)
   ),
   array (
     "Directions",         // unique name of this extra field (used in db)
     "Driving Directions", // how this field will be described to users
     EXTRA_MULTILINETEXT,  // type of field
     50,                   // width of text entry
     8                     // height of text entry
   ),
   array (
     "RoomLocation",       // unique name of this extra field (used in db)
     "Location",           // how this field will be described to users
     EXTRA_SELECTLIST,     // type of field
                           // List of options (first will be default)
     array ( "None", "Room 101", "Room 102", "Conf Room 8", "Conf Room 12" ),
     0                     // arg 2 (unused)
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
