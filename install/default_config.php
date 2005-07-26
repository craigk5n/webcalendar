<?php
$webcalConfig = array (
"application_name"=>"WebCalendar",
"LANGUAGE"=>"none",
"demo_mode"=>"N",
"require_approvals"=>"Y",
"groups_enabled"=>"N",
"user_sees_only_his_groups"=>"Y",
"categories_enabled"=>"Y",
"allow_conflicts"=>"N",
"conflict_repeat_months"=>"6",
"disable_priority_field"=>"N",
"disable_access_field"=>"N",
"disable_participants_field"=>"N",
"disable_repeating_field"=>"N",
"allow_view_other"=>"Y",
"remember_last_login"=>"N",
"allow_color_customization"=>"Y",
"BGCOLOR"=>"#FFFFFF",
"TEXTCOLOR"=>"#000000",
"H2COLOR"=>"#000000",
"CELLBG"=>"#C0C0C0",
"WEEKENDBG"=>"#D0D0D0",
"TABLEBG"=>"#000000",
"THBG"=>"#FFFFFF",
"THFG"=>"#000000",
"POPUP_FG"=>"#000000",
"POPUP_BG"=>"#FFFFFF",
"TODAYCELLBG"=>"#FFFF33",
"WEEK_START"=>"0",
"TIME_FORMAT"=>"12",
"DISPLAY_UNAPPROVED"=>"Y",
"DISPLAY_WEEKNUMBER"=>"Y",
"WORK_DAY_START_HOUR"=>"8",
"WORK_DAY_END_HOUR"=>"17",
"send_email"=>"N",
"EMAIL_REMINDER"=>"Y",
"EMAIL_EVENT_ADDED"=>"Y",
"EMAIL_EVENT_UPDATED"=>"Y",
"EMAIL_EVENT_DELETED"=>"Y",
"EMAIL_EVENT_REJECTED"=>"Y",
"auto_refresh"=>"N",
"nonuser_enabled"=>"Y",
"allow_html_description"=>"Y",
"reports_enabled"=>"N",
"DISPLAY_WEEKENDS"=>"Y",
"DISPLAY_DESC_PRINT_DAY"=>"Y",
"DATE_FORMAT"=>"__month__ __dd__, __yyyy__",
"TIME_SLOTS"=>"24",
"TIMED_EVT_LEN"=>"D",
"PUBLISH_ENABLED"=>"Y",
"DATE_FORMAT_MY"=>"__month__ __yyyy__",
"DATE_FORMAT_MD"=>"__month__ __dd__",
"CUSTOM_SCRIPT"=>"N",
"CUSTOM_HEADER"=>"N",
"CUSTOM_TRAILER"=>"N",
"bold_days_in_year"=>"Y",
"site_extras_in_popup"=>"N",
"add_link_in_views"=>"N",
"allow_conflict_override"=>"Y",
"limit_appts"=>"N",
"limit_appts_number"=>"6",
"public_access"=>"N",
"public_access_default_visible"=>"N",
"public_access_default_selected"=>"N",
"public_access_others"=>"Y",
"public_access_can_add"=>"N",
"public_access_add_needs_approval"=>"N",
"public_access_view_part"=>"N",
"nonuser_at_top"=>"Y",
"allow_external_users"=>"N",
"external_notifications"=>"N",
"external_reminders"=>"N",
"enable_gradients"=>"N",
"FONTS"=>"Arial, Helvetica, sans-serif",
"auto_refresh_time"=>"0",
"email_fallback_from"=>"youremailhere",
"STARTVIEW"=>"month.php",
"uac_enabled"=>"N",
"RSS_ENABLED"=>"N",
"TIMEZONE"=>"America/New_York",
"webcal_program_version"=>"v1.1.0-CVS" 
 );

function db_load_config () {
global $webcalConfig;
   while ( list ( $key, $val ) = each ( $webcalConfig ) ) {
    $res = dbi_query ( "SELECT cal_value FROM webcal_config " .
     "WHERE cal_setting  = '$key'", false, false );
   $sql = "INSERT INTO webcal_config ( cal_setting, cal_value ) " .
       "VALUES ('". $key . "', '" . $val . "')";
     if ( ! $res ) {
       dbi_query ( $sql );
   } else { //Sqlite returns $res always
     $row = dbi_fetch_row ( $res );
     if ( ! isset ( $row[0] ) ){
         dbi_query ( $sql );  
     }
     dbi_free_result ( $res );
    }  
 }
}

function db_load_admin () {
 $res = dbi_query ( "SELECT cal_login FROM webcal_user " .
 "WHERE cal_login  = 'admin'", false, false );
 $sql = "INSERT INTO webcal_user ( cal_login, cal_passwd, cal_lastname, cal_firstname, cal_is_admin ) 
VALUES ( 'admin', '21232f297a57a5a743894a0e4a801fc3', 'ADMINISTRATOR', 'DEFAULT', 'Y' );";
 if ( ! $res ) {
  dbi_query ( $sql );
 } else { //Sqlite returns $res always
  $row = dbi_fetch_row ( $res );
  if ( ! isset ( $row[0] ) ){
   dbi_query ( $sql );  
  }
  dbi_free_result ( $res );
 }  

}
?>
