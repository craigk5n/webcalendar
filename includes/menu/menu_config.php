<?php
/* $Id: menu_config.php,v 1.5.2.6 2008/03/31 21:06:21 umcesrjones Exp $
 *
 * This file contains a listing of all top menu config settings
 * Items can be enabled/disabled, or menuExtras can be added
 *
 *
*/

//To disable a menu item, simply set it's value to 0
//If you disable a root menu item, all it's
//children (item's indented directly below it) will also be disabled
$menuConfig = array (
'My Calendar'=>1,
  'Home'=>1,
  'Today'=>1,
  'This Week'=>1,
  'This Month'=>1,
  'This Year'=>1,
  'Exit'=>1,
'Events'=>1,
  'Add New Event'=>1,
  'Add New Task'=>1,
  'Delete Entries'=>1,
  'Unapproved Entries'=>1,
  'Export'=>1,
  'Import'=>1,
'Views'=>1,
  'Another Users Calendar'=>1,
  'My Views'=>1,
  'Manage Calendar of'=>1,
  'Manage Views'=>1,
'Reports'=>1,
  'Activity Log'=>1,
  'System Log'=>1,
  'System Log'=>1,
  'Security Audit'=>1,
  'Manage Reports'=>1,
  'My Reports'=>1,
'Settings'=>1,
  'NUC_Assistants'=>1,
  'NUC_Preferences'=>1,
  'Assistants'=>1,
  'Categories'=>1,
  'Layers'=>1,
  'My Profile'=>1,
  'Remote Calendars'=>1,
  'Preferences'=>1,
  'Public Calendar'=>1,
    'Public Preferences'=>1,
    'Public Unapproved Events'=>1,
  'System Settings'=>1,
  'User Access Control'=>1,
  'User Manager'=>1,
'Search'=>1,
  'Advanced Search'=>1,
'Help'=>1,
  'Help Contents'=>1,
  'About WebCalendar'=>1,
'Unapproved Icon'=>1,
'Printer'=>1,
'Login Fullname'=>1,  //Display user's fullname
'MENU_DATE_TOP'=>1,   //Display Month/Week/Year Selectors See also $MENU_DATE_TOP
'Login'=>1,
'Above Custom Header'=>0 //Display Menu before custom header
 );

$menuExtras = array ();
/* Define your custom menu items using the syntax below
   Allowable menu types are listed below followed by their parameters
   parameters marked with * are optional
       menu, title, url*, array ( item, submenu, divider, spacer)
       item, icon, title*, url*, target*
       submenu, icon, title, array ( item, submenu, divider, spacer)
       divider
       spacer
  Specify the position where your new menu will appear
  0 = before existing menu
  1-6 = inside existing menu
  7 = after exiting menu

//Builds a new menu with submenu elements that will appear
//in the third menu location
$menuExtras[2] =  array ( 'menu', 'My Example', '',
  array (
    array ( 'item', 'todo.png', 'New Todo', 'edit_entry.php?eType=task', ''),
    array ( 'item', 'week.png', 'View Week', 'week.php', ''),
    array ( 'divider' ),
    array ( 'submenu', 'manage_cal.png', 'External Links',
     array (
       array ( 'item', 'display.png', 'Google', 'http://www.google.com', '_blank'),
       array ( 'item', 'display.png', 'Craig&#39;s Site',
         'http://www.k5n.us/webcalendar.php', 'new')
     )
   )
 )
);
//Builds an icon menu item without any submenu options that will appear
//after all other menus
$menuExtras[7] =  array ( 'item', 'home.png', '', 'http://www.home.com', '_blank' )
*/
?>
