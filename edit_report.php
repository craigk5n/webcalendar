<?php
/**
 * Presents a HTML form to add or edit a report.
 * 
 * Input Parameters:
 * - <var>report_id</var> (optional) - the report id of the report to edit.  If
 *   blank, user is adding a new report.
 * - <var>public</var> (optional) - If set to '1' and user is an admin user,
 *   then we are creating a report for the public user.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 * @subpackage Reports
 *
 */

/*
 * Security:
 * If system setting $REPORTS_ENABLED is set to anything other than
 *   'Y', then don't allow access to this page.
 * If $ALLOW_VIEW_OTHER is 'N', then do not allow selection of
 *   participants.
 * If not an admin user, only report creator (cal_login in webcal_report)
 *   can edit/delete report.
 */

include_once 'includes/init.php';
load_user_categories ();

$updating_public = false;
$error = '';

if ( empty ( $REPORTS_ENABLED ) || $REPORTS_ENABLED != 'Y' ) {
  $error = print_not_auth () . '.';
}

if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $updating_public = true;
  $report_user = '__public__';
} else {
  $report_user = '';
}

$report_id = getValue ( 'report_id', '-?[0-9]+', true );

$adding_report = false;
if ( empty ( $report_id ) ) {
  $adding_report = true;
  $report_id = -1;
  $include_header = 'Y';
  $report_is_global = 'N';
  $report_allow_nav = 'Y';
}

$show_participants = true;
if ( $single_user == 'Y' || $DISABLE_PARTICIPANTS_FIELD == 'Y' ) {
  $show_participants = false;
}

if ( $login == '__public__' ) {
  $error = print_not_auth ();
}

$charset = ( ! empty ( $LANGUAGE )?translate( 'charset' ): 'iso-8859-1' );

$checked = ' checked="checked"';
$selected = ' selected="selected" ';
// Set date range options
$ranges = array (
  '0' => translate ( 'Tomorrow' ),
  '1' => translate ( 'Today' ),
  '2' => translate ( 'Yesterday' ),
  '3' => translate ( 'Day before yesterday' ),
  '10' => translate ( 'Next week' ),
  '11' => translate ( 'This week' ),
  '12' => translate ( 'Last week' ),
  '13' => translate ( 'Week before last' ),
  '20' => translate ( 'Next week and week after' ),
  '21' => translate ( 'This week and next week' ),
  '22' => translate ( 'Last week and this week' ),
  '23' => translate ( 'Last two weeks' ),
  '30' => translate ( 'Next month' ),
  '31' => translate ( 'This month' ),
  '32' => translate ( 'Last month' ),
  '33' => translate ( 'Month before last' ),
  '40' => translate ( 'Next year' ),
  '41' => translate ( 'This year' ),
  '42' => translate ( 'Last year' ),
  '43' => translate ( 'Year before last' ),
  '50' => translate ( 'Next 14 days' ),
  '51' => translate ( 'Next 30 days' ),
  '52' => translate ( 'Next 60 days' ),
  '53' => translate ( 'Next 90 days' ),
  '54' => translate ( 'Next 180 days' ),
  '55' => translate ( 'Next 365 days' ),
);

// Get list of users that the current user can see
if ( empty ( $error ) && $show_participants ) {
  $userlist = get_my_users ( '', 'view' );
  if ($NONUSER_ENABLED == 'Y' ) {
    //restrict NUC list if groups are enabled
    $nonusers = get_my_nonusers ( $login , true, 'view' );
    $userlist = ($NONUSER_AT_TOP == 'Y') ? array_merge($nonusers, $userlist) : 
      array_merge($userlist, $nonusers);
  }
  $userlistcnt = count ( $userlist );
}

// Default values
$page_template = "<dl>\${days}</dl>";
$day_template = "<dt><b>\${date}</b></dt>\n<dd><dl>\${events}</dl></dd>";
$event_template = "<dt>\${name}</dt>\n<dd>" .
  '<b>' . translate ( 'Date' ) . ":</b> \${date}<br />\n" .
  '<b>' . translate ( 'Time' ) . ":</b> \${time}<br />\n" .
  "\${description}</dd>\n";

//Setup option arrays
$page_options = array ( 
  'days', 'report_id' );
$day_options = array ( 
  'events', 'date', 'fulldate', 'report_id');
$event_options = array ( 
  'name',
  'description',
  'date',
  'fulldate',
  'time',
  'starttime',
  'endtime',
  'duration',
  'location',
  'url',
  'priority',
  'href',
  'user',
  'fullname',
  'report_id'
);
//generate clickable option lists
function print_options ( $textarea, $option ) {
  //use ASCII values for ${}
  echo '<a onclick="addMe( \'' . $textarea . '\', \'${' .
    $option . '}\' )">${' . $option . "}</a><br />\n";
}

if ( empty ( $error ) && $report_id >= 0 ) {
  $sql = 'SELECT cal_login, cal_report_id, cal_is_global, ' .
    'cal_report_type, cal_include_header, cal_report_name, ' .
    'cal_time_range, cal_user, cal_allow_nav, cal_cat_id, ' .
    'cal_include_empty, cal_show_in_trailer, cal_update_date ' .
    'FROM webcal_report ' .
    'WHERE cal_report_id = ?';
  //echo "SQL: $sql<br /><br />";
  $res = dbi_execute ( $sql, array( $report_id ) );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $i = 0;
      $report_login = $row[$i++];
      $report_id = $row[$i++];
      $report_is_global = $row[$i++];
      $report_type = $row[$i++];
      $report_include_header = $row[$i++];
      $report_name = $row[$i++];
      $report_time_range = $row[$i++];
      $report_user = $row[$i++];
      $report_allow_nav = $row[$i++];
      $report_cat_id = $row[$i++];
      $report_include_empty = $row[$i++];
      $report_show_in_trailer = $row[$i++];
      $report_update_date = $row[$i++];

      // Check permissions.
      if ( $show_participants && ! empty ( $report_user ) ) {
        $user_is_in_list = false;
        for ( $i = 0; $i < $userlistcnt; $i++ ) {
          if ( $report_user == $userlist[$i]['cal_login'] ) {
            $user_is_in_list = true;
          }
        }
        if ( ! $user_is_in_list && $report_login != $login && ! $is_admin ) {
          $error = print_not_auth ();
        }
      }
      if ( ! $is_admin && $login != $report_login ) {
        // If not admin, only creator can edit/delete the event
        $error = print_not_auth ();
      }
      
      // If we are editing a public user report we need to set $updating_public
      if ( $is_admin && $report_login == '__public__' ) {
        $updating_public = true;
       }
        
    } else {
      $error = translate ( 'Invalid report id' ) . ": $report_id";
    }
    dbi_free_result ( $res );
  } else {
    $error = db_error ();
  }
  $res = dbi_execute ( 'SELECT cal_template_type, cal_template_text ' .
    'FROM webcal_report_template ' .
    'WHERE cal_report_id = ?', array( $report_id ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] == 'P' ) {
        $page_template = $row[1];
      } else if ( $row[0] == 'D' ) {
        $day_template = $row[1];
      } else if ( $row[0] == 'E' ) {
        $event_template = $row[1];
      }
    }
    dbi_free_result ( $res );
  }
} else {
  // default values for new report
  $report_login = $login;
  $report_id = -1;
  $report_is_global = 'N';
  $report_type = 'html';
  $report_include_header = 'Y';
  $report_name = translate( 'Unnamed Report' );
  $report_time_range = 11; // current week
  //$report_user already set
  $report_allow_nav = 'Y';
  $report_cat_id = '';
  $report_include_empty = 'N';
  $report_show_in_trailer = 'N';
  $report_update_date = '';
}

print_header();
//echo "report_id: $report_id <br />\n";
//echo "report_name: $report_name <br />\n";
//echo "report_user: $report_user <br />\n";
?>

<h2><?php
if ( $updating_public ) {
  echo translate($PUBLIC_ACCESS_FULLNAME) . ' ';
}
if ( $adding_report ) {
  etranslate( 'Add Report' );
} else {
  etranslate( 'Edit Report' );
}
?></h2>

<?php
if ( ! empty ( $error ) ) {
  echo $error;
  echo print_trailer( false );
  exit;
}
?>


<form action="edit_report_handler.php" method="post" name="reportform">
<?php if ( $updating_public ) { ?>
  <input type="hidden" name="public" value="1" />
<?php } 
if ( ! $adding_report ) { ?>
  <input type="hidden" name="report_id" value="<?php echo $report_id?>" />
<?php } ?>

<table>
 <tr><td>
  <label for="rpt_name"><?php etranslate( 'Report name' )?>:</label></td><td>
  <input type="text" name="report_name" id="rpt_name" size="40" maxlength="50"
    value="<?php echo  $report_name; ?>" />
 </td></tr>
<?php
if ( $show_participants ) {
  $users = '<option value=""';
  if ( empty ( $report_user ) ) {
    $users .= $selected;
  }
  $users .= '>' . translate ( 'Current User' ) . "</option>\n";
  for ( $i = 0; $i < $userlistcnt; $i++ ) {
    $users .= '<option value="' . $userlist[$i]['cal_login'] . '"';
    if ( ! empty ( $report_user ) ) {
      if ( $report_user == $userlist[$i]['cal_login'] ) {
        $users .= $selected;
      }
    } 
    $users .= '>' . $userlist[$i]['cal_fullname'] . "</option>\n";
  }
  echo '<tr><td><label for="rpt_user">' .
    ucfirst ( translate ( 'user' ) ) . ":</label></td>\n";
  echo "<td><select name=\"report_user\" id=\"rpt_user\" size=\"1\">$users\n";
  echo "</select>\n";
  echo "</td></tr>\n";
}

if ( $is_admin ) { ?>
<tr><td><label>
 <?php etranslate( 'Global' )?>:</label></td><td>
 <label><input type="radio" name="is_global" value="Y"
  <?php if ( $report_is_global != 'N' ) echo $checked; ?> 
    />&nbsp;<?php etranslate ( 'Yes') ?></label>&nbsp;&nbsp;&nbsp;
  <label><input type="radio" name="is_global" value="N"
    <?php if ( $report_is_global == 'N' ) echo $checked; ?>
    />&nbsp;<?php etranslate ( 'No') ?></label>
</td></tr>
<?php } 

// The report will always be shown in the trailer for the creator
// of the report.  For admin users who create a global report,
// allow option of adding to all users trailer.
if ( $is_admin ) {
?>
<tr><td><label>
 <?php etranslate( 'Include link in trailer' )?>:</label></td><td>
 <label><input type="radio" name="show_in_trailer" value="Y"
  <?php if ( $report_show_in_trailer != 'N' ) echo $checked; ?> 
  />&nbsp;<?php etranslate ( 'Yes') ?></label>&nbsp;&nbsp;&nbsp;
 <label><input type="radio" name="show_in_trailer" value="N"
  <?php if ( $report_show_in_trailer == 'N' ) echo $checked; ?> 
  />&nbsp;<?php etranslate ( 'No') ?></label>
</td></tr>
<?php } ?>
<tr><td><label>
 <?php etranslate( 'Include standard header/trailer' )?>:&nbsp;&nbsp;&nbsp;&nbsp;
   </label></td><td>
 <label><input type="radio" name="include_header" value="Y"
   <?php if ( $report_include_header != 'N' ) echo $checked; ?> 
   />&nbsp;<?php etranslate ( 'Yes') ?></label>&nbsp;&nbsp;&nbsp;
 <label><input type="radio" name="include_header" value="N"
   <?php if ( $report_include_header == 'N' ) echo $checked; ?> 
   />&nbsp;<?php etranslate ( 'No') ?></label>
</td></tr>
<tr><td>
 <label for="rpt_time_range"><?php etranslate( 'Date range' )?>:</label></td><td>
 <select name="time_range" id="rpt_time_range">
  <?php
    while ( list ( $num, $descr ) = each ( $ranges ) ) {
      echo "<option value=\"$num\"";
      if ( $report_time_range == $num ) {
        echo $selected;
      }
      echo ">$descr</option>\n";
    }
  ?></select>
</td></tr>
<tr><td>
 <label for="rpt_cat_id"><?php etranslate( 'Category' )?>:</label></td><td>
 <select name="cat_id" id="rpt_cat_id">
  <option value=""><?php etranslate( 'None' ) ?></option>
  <?php
    while ( list ( $K, $V ) = each ( $categories ) ) {
      echo "<option value=\"$K\"";
      if ( $report_cat_id == $K ) {
        echo $selected;
      }
      echo ">{$V['cat_name']}</option>\n";
    }
  ?></select>
</td></tr>
<tr><td><label>
 <?php etranslate( 'Include previous/next links' )?>:</label></td><td>
 <label><input type="radio" name="allow_nav" value="Y"
   <?php if ( $report_allow_nav != 'N' ) echo $checked; ?> 
   />&nbsp;<?php etranslate ( 'Yes') ?></label>&nbsp;&nbsp;&nbsp;
 <label><input type="radio" name="allow_nav" value="N"
   <?php if ( $report_allow_nav == 'N' ) echo $checked; ?> 
   />&nbsp;<?php etranslate ( 'No') ?></label>
</td></tr>
<tr><td><label>
 <?php etranslate( 'Include empty dates' )?>:</label></td><td>
 <label><input type="radio" name="include_empty" value="Y"
   <?php if ( $report_include_empty != 'N' ) echo $checked; ?> 
   />&nbsp;<?php etranslate ( 'Yes') ?></label>&nbsp;&nbsp;&nbsp;
 <label><input type="radio" name="include_empty" value="N"
   <?php if ( $report_include_empty == 'N' ) echo $checked; ?> 
   />&nbsp;<?php etranslate ( 'No') ?></label>
</td></tr>
</table>

<table>
 <tr><td>&nbsp;</td><td>&nbsp;</td><td colspan="2"><label>
  <?php etranslate( 'Template variables' )?></label>
 </td></tr>
 <tr><td valign="top"><label>
  <?php etranslate( 'Page template' )?>:</label></td><td>
  <textarea rows="12" cols="60" name="page_template"><?php echo htmlentities ( $page_template, ENT_COMPAT, $charset )?></textarea>
  </td><td class="aligntop cursoradd" colspan="2">
<?php
  foreach ( $page_options as $option ) { 
   print_options ( 'page_template', $option );
  }
 ?>
 </td></tr>
 <tr><td valign="top"><label>
  <?php etranslate( 'Day template' )?>:</label></td><td>
  <textarea rows="12" cols="60" name="day_template"><?php echo htmlentities ( $day_template, ENT_COMPAT, $charset )?></textarea>
  </td><td class="aligntop cursoradd" colspan="2">
<?php
  foreach ( $day_options as $option ) { 
   print_options ( 'day_template', $option );
  }
 ?>
 </td></tr>
 <tr><td valign="top"><label>
  <?php etranslate( 'Event template' )?>:</label></td><td>
  <textarea rows="12" cols="60" name="event_template" id="event_template"><?php 
    echo htmlentities ( $event_template, ENT_COMPAT, $charset )?></textarea>
  </td><td class="aligntop cursoradd" width="150px">
<?php
  foreach ( $event_options as $option ) { 
   print_options ( 'event_template', $option );
  }
  echo '</td><td class="aligntop cursoradd">';
  $extra_names = get_site_extras_names( EXTRA_DISPLAY_REPORT );
  if ( count ( $extra_names ) > 0 ) 
    echo '<label>' .translate( 'Site Extras' ). '</label><br />';
  foreach ( $extra_names as $name ) { 
    print_options ( 'event_template', 'extra:' . $name );
 } 
?>
 </td></tr>
 <tr><td colspan="4">
  <input type="submit" value="<?php etranslate( 'Save' )?>" />
<?php if ( ! $adding_report ) { ?>
  &nbsp;&nbsp;<input type="submit" name="delete" value="<?php etranslate( 'Delete' );?>"
  onclick="return confirm('<?php 
  echo str_replace ( 'XXX', translate ( 'report' ), $translations['Are you sure you want to delete this XXX?'] ) ?>');" />
<?php } ?>
 </td></tr>
</table>
</form>
<script type="text/javascript" language="javascript">
<!-- <![CDATA[
  //This script borrowed from phpMyAdmin with some mofification
	function addMe (areaname, myValue) {
    var textarea = document.reportform.elements[areaname];
	  //IE support
	  if (document.selection) {
	    textarea.focus();
	    sel = document.selection.createRange();
	    sel.text = myValue;
	  }
	  //MOZILLA/NETSCAPE support
	  else if (textarea.selectionStart || textarea.selectionStart == '0') {
	    var startPos = textarea.selectionStart;
	    var endPos = textarea.selectionEnd;
	    textarea.value = textarea.value.substring(0, startPos)
	    + myValue
	    + textarea.value.substring(endPos, textarea.value.length);
	  } else {
	    textarea.value += myValue;
	  }
	}
//]]> -->
</script>
<?php echo print_trailer(); ?>

