<?php
require_once 'includes/init.php';
require_once 'includes/help_list.php';

if ( empty ( $SERVER_SOFTWARE ) )
  $SERVER_SOFTWARE = $_SERVER['SERVER_SOFTWARE'];

if ( empty ( $HTTP_USER_AGENT ) )
  $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];

  print_header ( '', '', '', true );
  echo $helpListStr . '
    <h2>' . translate ( 'Report Bug' ) . '</h2>
    <p>' .
  translate ( 'Please include all the information below when reporting a bug.' )
   . ( $LANGUAGE != 'English-US' ? ' '
// translate ( 'Also, please use English rather than' )
     . str_replace ('XXX', translate ( get_browser_language ( true ) ),
     translate ( 'Also, please use English rather than XXX.' ) ) : '' ) . '</p>
    <form action="https://github.com/craigk5n/webcalendar/issues" target="_blank">
      <button type="submit">' . translate ( 'Report Bug' ) . '</button>
    </form>
    <h3>' . translate ( 'System Settings' ) . '</h3>
    <div>';
  $tmp_arr = [
    'PROGRAM_NAME' => $PROGRAM_NAME,
    'SERVER_SOFTWARE' => $SERVER_SOFTWARE,
    'Web Browser' => $HTTP_USER_AGENT,
    'PHP Version' => phpversion (),
    'Default Encoding' => ini_get ( 'default_charset' ),
    'db_type' => $db_type,
    'readonly' => $readonly,
    'single_user' => $single_user,
    'single_user_login' => $single_user_login,
    'use_http_auth' => ( $use_http_auth ? 'Y' : 'N' ),
    'user_inc' => $user_inc];
  $res = dbi_execute ( 'SELECT cal_setting, cal_value
    FROM webcal_config ORDER BY cal_setting' );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $tmp_arr[ $row[0] ] = $row[1];
    }
    dbi_free_result ( $res );
  }

  list_help ( $tmp_arr );

  echo '
    </div>
    ' . print_trailer ( false, true, true );

  ?>
