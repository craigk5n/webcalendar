<?php // $Id$
/**
 * This file, in conjunction with "themes/theme_config.php",
 * contains a listing of all the current WebCalendar config settings
 * and their default values.
 */

$tmp = 'themes/theme_config.php';
include_once ( file_exists( $tmp ) ? '' : '../' ) . $tmp;

$webcalConfig = array(
  'ADMIN_OVERRIDE_UAC'              => 'Y',
  'APPROVE_ASSISTANT_EVENT'         => 'Y',
  'BGIMAGE'                         => '',
  'BGREPEAT'                        => 'repeat fixed center',
  'CAPTIONS'                        => '#B04040',
  'DATE_FORMAT_TASK'                => 'LANGUAGE_DEFINED',
  'DISABLE_CROSSDAY_EVENTS'         => 'N',
  'DISABLE_REMINDER_FIELD'          => 'N',
  'DISABLE_URL_FIELD'               => 'Y',
  'DISPLAY_END_TIMES'               => 'N',
  'DISPLAY_CREATED_BYPROXY'         => 'Y',
  'DISPLAY_LONG_DAYS'               => 'N',
  'DISPLAY_MINUTES'                 => 'N',
  'DISPLAY_MOON_PHASES'             => 'N',
  'EMAIL_ASSISTANT_EVENTS'          => 'Y',
  'EMAIL_EVENT_CREATE'              => 'N',
  'EMAIL_FALLBACK_FROM'             => 'youremailhere',
  'ENABLE_CAPTCHA'                  => 'N',
  'ENABLE_ICON_UPLOADS'             => 'N',
  'ENTRY_SLOTS'                     => '144',
  'GENERAL_USE_GMT'                 => 'Y',
  'MENU_DATE_TOP'                   => 'Y',
  'MENU_ENABLED'                    => 'Y',
  'MENU_THEME'                      => 'default',
  'MYEVENTS'                        => '#006000',
  'NEXTMONTHBG'                     => '#D0D9D0',
  'PARTICIPANTS_IN_POPUP'           => 'N',
  'PREVMONTHBG'                     => '#D9D0D0',
  'REMINDER_DEFAULT'                => 'N',
  'REMINDER_OFFSET'                 => '240',
  'REMINDER_WITH_DATE'              => 'N',
  'REMOTES_ENABLED'                 => 'N',
  'TIME_SPACER'                     => '&raquo;&nbsp;',
  'TIMEZONE'                        => 'America/New_York',
  'USER_PUBLISH_ENABLED'            => 'Y',
  'USER_PUBLISH_RW_ENABLED'         => 'Y',
  'USER_RSS_ENABLED'                => 'N',
  'USER_SORT_ORDER'                 => 'cal_lastname, cal_firstname',
  'WEBCAL_PROGRAM_VERSION'          => translate( 'PROGRAM_VERSION' ),
  'WEEKEND_START'                   => '6',
  'WEEKNUMBER'                      => '#FF6633',
  );

$webcalConfig = sort( array_merge( $webcal_theme, $webcalConfig ) );

/**
 * db_load_config (needs description)
 *
 * This function is defined here because "admin.php" calls it during startup.
 */
function db_load_config() {
  global $webcalConfig;

  $sql = 'INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( ?, ? )';

  while( list( $key, $val ) = each( $webcalConfig ) ) {
    $res = dbi_execute( 'SELECT cal_value FROM webcal_config
      WHERE cal_setting = ?', array( $key ), false, false );
    if( ! $res )
      dbi_execute( $sql, array( $key, $val ) );
    else { // SQLite returns $res always.
      $row = dbi_fetch_row( $res );
      if( ! isset( $row[0] ) )
        dbi_execute( $sql, array( $key, $val ) );

      dbi_free_result( $res );
    }
  }
}

?>
