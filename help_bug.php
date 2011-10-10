<?php // $Id$
include_once 'includes/init.php';
include_once 'includes/help_list.php';

if ( empty ( $SERVER_SOFTWARE ) )
  $SERVER_SOFTWARE = $_SERVER['SERVER_SOFTWARE'];

if ( empty ( $HTTP_USER_AGENT ) )
  $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];

  ob_start();
  print_header ( '', '', '', true );

  echo $helpListStr . '
    <h2>' . translate ( 'Report Bug' ) . '</h2>
    <p>' .
  translate ( 'include info to report bugs' )
   . ( $LANGUAGE != 'English-US' ? ' '
     . str_replace ('XXX', translate ( get_browser_language ( true ) ),
     translate ( 'please use English not XXX' ) ) : '' ) . '</p>
    <form action="http://sourceforge.net/tracker/" target="_new">
      <input type="hidden" name="func" value="add">
      <input type="hidden" name="group_id" value="3870">
      <input type="hidden" name="atid" value="103870">
      <input type="submit" value="' . translate ( 'Report Bug' ) . '">
    </form>
    <h3>' . translate ( 'System Settings' ) . '</h3>
    <div>';
  // Since these ae literals and not translated,
  // the puncuation either has to stay here or not show at all.
  $tmp_arr = array (
    'PROGRAM_NAME:'     => $PROGRAM_NAME,
    'SERVER_SOFTWARE:'  => $SERVER_SOFTWARE,
    'Web Browser:'      => $HTTP_USER_AGENT,
    'PHP Version:'      => phpversion(),
    'Default Encoding:' => ini_get ( 'default_charset' ),
    'db_type:'          => $db_type,
    'readonly:'         => $readonly,
    'single_user:'      => $single_user,
    'single_user_login:'=> $single_user_login,
    'use_http_auth:'    => ( $use_http_auth ? 'Y' : 'N' ),
    'user_inc:'         => $user_inc,
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
  echo '
    </div>' . print_trailer ( false, true, true );
  ob_end_flush();

  ?>
