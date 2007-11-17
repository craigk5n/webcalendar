<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';

if ( empty ( $SERVER_SOFTWARE ) )
  $SERVER_SOFTWARE = $_SERVER['SERVER_SOFTWARE'];

if ( empty ( $HTTP_USER_AGENT ) )
  $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];

  build_header ( '', '', '', 29 );

  ob_start ();

  echo $helpListStr . '
    <h2>' . translate ( 'Report Bug' ) . '</h2>
    <p>' .
  translate ( 'Please include all the information below when reporting a bug.' )
   . ( getPref ( 'LANGUAGE' ) != 'English-US' ? ' '
     . translate ( 'Also, please use <strong>English</strong> rather than' )
     . ' ' . translate ( get_browser_language ( true ) ) . '.' : '' ) . '</p>
    <form action="http://sourceforge.net/tracker/" target="_new">
      <input type="hidden" name="func" value="add" />
      <input type="hidden" name="group_id" value="3870" />
      <input type="hidden" name="atid" value="103870" />
      <input type="submit" value="' . translate ( 'Report Bug' ) . '" />
    </form>
    <h3>' . translate ( 'System Settings' ) . '</h3>
    <div>';
  $tmp_arr = array ( 'PROGRAM_NAME' => PROGRAM_NAME,
    'SERVER_SOFTWARE' => $SERVER_SOFTWARE,
    'Web Browser' => $HTTP_USER_AGENT,
    'PHP Version' => phpversion (),
    'Default Encoding' => ini_get ( 'default_charset' ),
    'db_type' => _WC_DB_TYPE,
    'readonly' => ( _WC_READONLY ? 'Y' : 'N' ),
    'single_user' => ( _WC_SINGLE_USER ? 'Y' : 'N' ),
    'single_user_login' => _WC_SINGLE_USER_LOGIN,
    'use_http_auth' => ( _WC_HTTP_AUTH ? 'Y' : 'N' ),
    'user_inc' => _WC_USER_INC,
    );
  $res = dbi_execute ( 'SELECT cal_setting, cal_value
    FROM webcal_config ORDER BY cal_setting' );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $tmp_arr[ $row[0] ] = $row[1];
    }
    dbi_free_result ( $res );
  }

  list_help ( $tmp_arr );
  ob_end_flush ();
  echo '
    </div>
    ' . print_trailer ( false, true, true );

  ?>
