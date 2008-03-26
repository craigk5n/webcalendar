<?php
/* $Id$
 *
 * The file contains all the steps and values to be displayed
 * during installation
 *
 * NOTE: Do all translations in this file
 */
$installConfig = array (
  array (
    'title'=>translate ('File Locations' ),
    'formname'=>'file_loc',
    'text'=>translate( 'file location text...' ),
    'Server URL'=>array (
      'text'=>translate( 'server-url-help' ),
      'type'=>'input',
      'value'=>$_SESSION['SERVER_URL'],
			'size'=>100),
    'Public cache'=>array (
      'type'=>'input',
      'text'=>translate( 'public-cache-help' ),
      'value'=>$_SESSION['PUBLIC_CACHE'],
      'size'=>20),
     'Database cache'=>array (
      'type'=>'input',
      'text'=>translate( 'database-cache-help' ),
      'value'=>$_SESSION['DB_CACHE'],
      'size'=>20)
  ),
  array (
    'title'=>translate ('Database Settings' ),
    'formname'=>'db_settings',
    'text'=>translate( 'db setting text...' ),
    'Database Type'=>array (
      'text'=>translate( 'Database Type' ),
      'type'=>'select',
      'value'=>$_SESSION['SERVER_URL'],
      'options'=>array ( 'MySql', 'ODBC', 'SQLite' ) ),
    'Server'=>array (
      'type'=>'input',
      'text'=>translate( 'Server' ),
      'value'=>$_SESSION['PUBLIC_CACHE'],
      'size'=>20),
     'Login'=>array (
      'type'=>'input',
      'text'=>translate( 'Login' ),
      'value'=>$_SESSION['DB_CACHE'],
      'size'=>20),
     'Password'=>array (
      'type'=>'input',
      'text'=>translate( 'Password' ),
      'value'=>$_SESSION['DB_CACHE'],
      'size'=>20),
     'Database Name'=>array (
      'type'=>'input',
      'text'=>translate( 'Database Name' ),
      'value'=>$_SESSION['DB_CACHE'],
      'size'=>20),
     'Connection Persistence'=>array (
      'type'=>'input',
      'text'=>translate( 'Connection Persistence' ),
      'value'=>$_SESSION['DB_CACHE'],
      'size'=>20)
  ),
);

?>
