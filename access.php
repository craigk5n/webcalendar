<?php
/*
 * $Id$
 *
 * This page is used to manage user access rights.
 *
 * It has three different modes:
 * - list users to manage (no parameters)
 * - manage a single user's rights (just "user" parameter)
 *   this will include which functions the user can access and
 *   (if $ALLOW_VIEW_OTHER is 'Y') which calendars thay can view/edit/approve
 * - update the database (form handler)
 *
 * Input Parameters:
 *  user - specifies which user to manage, a form will be presented
 *         that allows editing rights of this user
 *
 *  access_N - where N is 0 to ACCESS_NUMBER_FUNCTIONS as defined in
 *                 includes/access.php.  Each should be either 'Y' or 'N'.
 *
 *
 */
include_once 'includes/init.php';

if ( ! access_is_enabled () ) {
  etranslate ( 'You are not authorized' );
  exit;  
}
//print_r ( $_POST );
// Are we handling the access form?
// If so, do that, then redirect

  // Handle function access first
if ( getPostValue ( 'auser' ) != '' && getPostValue ( 'submit' ) != '') { 
  $auser = getPostValue ( 'auser' );
  $perm = '';
  for ( $i = 0; $i < ACCESS_NUMBER_FUNCTIONS; $i++ ) {
    $val = getPostValue ( 'access_' . $i );
    $perm .= ( $val == 'Y' ) ? 'Y' : 'N';
  }

  $sql = "DELETE FROM webcal_access_function WHERE cal_login = ?";
  dbi_execute ( $sql, array( $auser ) );

  $sql = "INSERT INTO webcal_access_function ( cal_login, cal_permissions ) " .
    "VALUES ( ?, ? )";
  if ( ! dbi_execute ( $sql, array( $auser, $perm ) ) ) {
    die_miserable_death ( translate ( 'Database error' ) . ': ' .
      dbi_error () );
  }

}
// Are we handling the other user form?
// If so, do that, then redirect
if ( getPostValue ( 'otheruser' ) != '' && getPostValue ( 'submit' ) != '') { 
  $puser = getPostValue ( 'guser' );
  $pouser = getPostValue ( 'otheruser' );  
  if ( empty ( $ALLOW_VIEW_OTHER ) || $ALLOW_VIEW_OTHER == 'Y' ) {
    // Handle access to other users' calendars
    //if user is not admin, reverse values so they are granting
    //access to their own calendar
    if ( ! $is_admin )
      list($puser, $pouser) = array($pouser, $puser);

    dbi_execute ( "DELETE FROM webcal_access_user WHERE cal_login = ? AND " .
      "cal_other_user = ?", array( $puser, $pouser ) );
      
    if ( empty ( $pouser ) )
      break;
    $view_total = $edit_total = $approve_total = 0;
    for ( $i=1;$i<=256; ) {
      //echo $i . " "  .getPostValue ( 'v_' . $i ) . "<br>";
      $view_total    += getPostValue ( 'v_' . $i );
      $edit_total    += getPostValue ( 'e_' . $i );
      $approve_total += getPostValue ( 'a_' . $i );
      $i += $i;

    }
    $invite = ( strlen ( getPostValue ( 'invite' ) )?getPostValue ( 'invite' ):'N' );
    $email = ( strlen ( getPostValue ( 'email' ) )?getPostValue ( 'email' ):'N' );
    $time = ( strlen ( getPostValue ( 'time' ) )?getPostValue ( 'time' ):'N' );
    $view = ( $view_total > 0 ) ? $view_total : 0;
    $edit = ( $edit_total > 0 && $puser != '__public__' ? $edit_total : 0 );
    $approve = ( $approve_total > 0 && $puser != '__public__' ? $approve_total : 0 );
    
    $sql = "INSERT INTO webcal_access_user " .
      "( cal_login, cal_other_user, cal_can_view, cal_can_edit, " .
      "cal_can_approve, cal_can_invite, cal_can_email, cal_see_time_only ) VALUES " .
      "( ?, ?, ?, ?, ?, ?, ?, ? )";
    if ( ! dbi_execute ( $sql, array( $puser, $pouser, 
      $view, $edit, $approve, $invite, $email, $time ) ) ) {
      die_miserable_death ( translate ( 'Database error' ) . ': ' .
        dbi_error () );
    }
  }
}
$otheruser = '';
$checked = ' checked="checked" ';
$guser = getPostValue ( 'guser' );
if ( $guser == '__default__' ) $user_fullname = 'DEFAULT CONFIGURATION';
$otheruser = getPostValue ( 'otheruser' );
if ( $otheruser == '__default__' ) {
  $otheruser_fullname = 'DEFAULT CONFIGURATION';
  $otheruser_login  = '__default__';
}

if ( ! empty ( $otheruser ) ) {
  if ( empty ( $ALLOW_VIEW_OTHER ) || $ALLOW_VIEW_OTHER == 'Y' ) {
    $query_param = array( $guser, $otheruser );
    //if user is not admin, reverse values so they are granting
    //access to their own calendar
    if ( ! $is_admin )
      $query_param = array( $otheruser, $guser );
    user_load_variables ( $otheruser, 'otheruser_' );
    // Now load all the data from webcal_access_user
    $res = dbi_execute ( "SELECT cal_other_user, cal_can_view, cal_can_edit, " .
      "cal_can_approve, cal_can_invite, cal_can_email, cal_see_time_only " .
      "FROM webcal_access_user WHERE cal_login = ? AND cal_other_user = ?", 
      $query_param );
    assert ( '$res' );
    $op = array ();
    while ( $row = dbi_fetch_row ( $res ) ) {
      $op = array (
        'cal_other_user' => $row[0],
        'view' => $row[1],
        'edit' => $row[2],
        'approve' => $row[3],
        'invite' => $row[4],
        'email' => $row[5],
        'time' => $row[6]
      );
    }
    dbi_free_result ( $res );
  }
}

$BodyX = ( ! empty ( $op['time'] ) && $op['time'] == 'Y' ? 
  'onload="enableAll( true );"' : '' );
print_header ('','', $BodyX);

if ( ! empty ( $guser ) || ! $is_admin ) {
 if ( $is_admin ) {
  // Present a page to allow editing a user's rights
  user_load_variables ( $guser, 'user_' );
  $uacStr = translate ( 'User Access Control' );
  $adminStr = translate( 'Admin' );
?>  
  <h2><?php echo $uacStr;?>:<?php echo $user_fullname;?></h2>

  <a title="<?php echo $adminStr;?>" class="nav" href="adminhome.php">&laquo;&nbsp;<?php 
  echo $adminStr;?></a><br /><br />

  <form action="access.php" method="post" name="accessform">
  <input type="hidden" name="auser" value="<?php echo $guser;?>" />
  <input type="hidden" name="guser" value="<?php echo $guser;?>" />
  <table border="0" cellspacing="10"><tbody><tr><td valign="top">
  <?php

  $access = access_load_user_functions ( $guser );

  $div = ceil ( ACCESS_NUMBER_FUNCTIONS / 4 );

  for ( $i = 0; $i < ACCESS_NUMBER_FUNCTIONS; $i++ ){
    // Public access can never use some of these functions
    $show = true;
    if ( $guser == '__public__' ) {
      switch ( $i ) {
        case ACCESS_VIEW_MANAGEMENT:
        case ACCESS_ACTIVITY_LOG:
        case ACCESS_ADMIN_HOME:
        case ACCESS_USER_MANAGEMENT:
        case ACCESS_ACCESS_MANAGEMENT:
        case ACCESS_PREFERENCES:
        case ACCESS_IMPORT:
        case ACCESS_ASSISTANTS:
        case ACCESS_ACCOUNT_INFO:
        case ACCESS_SYSTEM_SETTINGS:
        case ACCESS_CATEGORY_MANAGEMENT:
          // skip these...
          $show = false;
          break;
      }
    }
    if ( $show ) {
      $yesno = substr ( $access, $i, 1 );
      $checkit = ( $yesno != 'N' ) ? $checked : '';
      echo '<label for="access_' . $i . '">';
      echo '<input type="checkbox" name="access_' . $i .
        '" id="access_' . $i . '" value="Y" ' . $checkit . "/>\n";
      echo access_get_function_description ( $i );
      echo "</label><br />\n";
    }
    if ( ($i + 1 )%$div == 0 )
      echo "</td>\n<td valign=\"top\">\n";
  }
  ?>
  </td></tr>
  </tbody></table>
    <input type="button" value="<?php etranslate( 'Cancel' ); ?>"
    onclick="document.location.href='access.php'" />
  <input type="submit" name="submit"  value="<?php etranslate( 'Save' ); ?>" />
  </form>
<?php
 } //end is_admin test
    // Get list of users that this user can see (may depend on group settings)
    // along with all nonuser calendars
   // if ( $guser != '__default__' ) {
      if ( ! $is_admin ) {
        $guser = $login;
        $pagetitle = translate ( 'Grant This User Access to My Calendar' );
      } else {
        $pagetitle = translate ( "Allow Access to Other Users' Calendar" );    
      }
      if ( $guser == '__default__' ) {
        $userlist = array ( '__default__' );
        $otheruser = '__default__';        
      } else { 
        $userlist = get_list_of_users ( $guser );
        echo "<h2>$pagetitle</h2>\n";
        echo '<form action="access.php" method="post" name="SelectOther">' . "\n";
        echo "<input type=\"hidden\" name=\"guser\" value=\"$guser\" />\n";
        echo '<select name="otheruser" onchange="document.SelectOther.submit()">' . "\n";

        //add a DEFAULT CONFIGURATION to be used as a mask  
        echo '<option value="__default__">'.
          translate ( 'DEFAULT CONFIGURATION' )."</option>\n";
        $selected ='';
        $cnt = count ( $userlist );
        for ( $i = 0; $i < $cnt; $i++ ) {
          if ( $userlist[$i]['cal_login'] != $guser  ) {
            $selected = ( ! empty ( $otheruser ) && 
              $otheruser == $userlist[$i]['cal_login'] ? ' selected="selected"':'');
            echo '<option value="' .$userlist[$i]['cal_login']. '"' . 
              $selected .'>'. $userlist[$i]['cal_fullname']."</option>\n";
          }
       }
       echo '</select>';
       echo '<input type="submit"  value="' . translate( 'Go' ) . '" />';
       echo "</form>\n";

     if (  empty ( $otheruser ) ) {
       print_trailer ();
       echo "</body></html>\n";
       exit;
    }
  }
}

if ( ! empty ( $otheruser ) ) {
  if ( empty ( $ALLOW_VIEW_OTHER ) || $ALLOW_VIEW_OTHER == 'Y' ) {
    echo "<form action=\"access.php\" method=\"post\" name=\"EditOther\">\n";
    echo "<input type=\"hidden\" name=\"guser\" value=\"$guser\" />\n";        
    echo "<input type=\"hidden\" name=\"otheruser\" value=\"$otheruser\" />\n";
?>
    <br />
    
    <table cellpadding="5" cellspacing="0">
    <tbody>
    <tr>
<?php if ( $guser == '__public__' ) { ?>
      <th class="boxtop boxbottom" width="60%" align="center">
      <?php etranslate('Calendar'); ?></th>
      <th class="boxtop boxbottom" width="20%">
      <?php etranslate( 'Type' ); ?></th>
      <th class="boxtop boxbottom boxright" colspan="3" width="20%">
      <?php etranslate( 'View Event' ); ?></th>
<?php } else   {//if ( $guser != '__default__' ) { ?>
      <th class="boxtop boxbottom" width="25%">
      <?php echo $otheruser_fullname; ?></th>
      <th class="boxtop boxbottom" width="15%">
      <?php etranslate( 'Type' ); ?></th>
      <th width="15%" colspan="3" class="boxtop boxbottom">
        <?php etranslate( 'View' ); ?></th>
      <th width="15%" colspan="3" class="boxtop boxbottom">
        <?php etranslate( 'Edit' ); ?></th>
      <th width="15%" colspan="3" class="boxtop boxright boxbottom">
        <?php etranslate( 'Approve/Reject' ); ?></th>
<?php } ?>
</tr>
    <?php
        $access_type = array();
        $access_type[1] = translate ( 'Events' );
        $access_type[2] = translate ( 'Tasks' );
        $access_type[4] = translate ( 'Journals' );      
        for ( $j =1; $j < 5;$j++ ) {
          $bottomedge = ''; 
          if ( $j ==3 ) continue; 
          echo "<tr>";
          if ( $j == 1) {
            echo '<td class="boxleft leftpadded">' .
              '<input type="checkbox" value="Y" name="invite"' . 
              ( !empty ( $op['invite'] ) && $op['invite'] == 'N' ? 
                '': $checked ) . ' />' . 
                  translate ( 'Can Invite' ) . "</td>\n";
          } else if ( $j == 2 ) {
            echo '<td class="boxleft leftpadded">' .
              '<input type="checkbox" value="Y" name="email"' . 
              ( !empty ( $op['email'] ) && $op['email'] == 'N' ? 
                '': $checked ) . ' />' . 
                  translate ( 'Can Email' ) . "</td>\n";          
          } else {
            echo '<td class="boxleft boxbottom leftpadded">' .
              '<input type="checkbox" value="Y" name="time"' . 
              ( ! empty ( $op['time'] ) && $op['time'] == 'Y' ? 
              $checked :'') . ' onclick="enableAll(this.checked);"/>' . 
                translate ( 'Can See Time Only' ) . "</td>\n";
            $bottomedge = 'boxbottom';          
          }
          echo "<td align=\"center\" class=\"boxleft $bottomedge\">". 
            $access_type[$j] . "</td>\n<td align=\"center\" ".
            "class=\"boxleft pub $bottomedge\">";
          echo "<input type=\"checkbox\" value=\"$j\" name=\"v_" . $j .'"' . 
            ( ! empty ( $op['view'] ) && ( $op['view'] & $j ) ? 
              $checked :'')  . " /></td><td class=\"conf $bottomedge\">\n";
          echo '<input type="checkbox" value="'.($j*8).'" name="v_' . ($j* 8 ) .
            '"' . ( ! empty ( $op['view'] ) && ( $op['view'] & ($j*8) )? 
            $checked :'')  . " /></td><td class=\"priv $bottomedge\">\n";
          echo '<input type="checkbox" value="'.($j*64).'" name="v_' . ($j*64 )  .
            '"' . ( ! empty ( $op['view'] ) && ( $op['view'] & ($j*64))? 
            $checked :'')  . " />\n";
          echo "</td>\n";              
          if ( $guser != '__public__' ) {
            echo "<td align=\"center\" class=\"boxleft pub $bottomedge\">";
          echo "<input type=\"checkbox\" value=\"$j\" name=\"e_" . $j .'"' . 
            ( ! empty ( $op['edit'] ) && ( $op['edit'] & $j )? $checked :'')  . 
              " /></td><td class=\"conf $bottomedge\">\n";
          echo '<input type="checkbox" value="'.($j*8).'" name="e_' . ($j* 8 ) .
            '"' . ( ! empty ( $op['edit'] ) && ( $op['edit'] & ($j*8) )? 
            $checked :'')  . " /></td><td class=\"priv $bottomedge\">\n";
          echo '<input type="checkbox" value="'.($j*64).'" name="e_' . ($j*64 )  .
            '"' . ( ! empty ( $op['edit'] ) && ( $op['edit'] & ($j*64) )? 
            $checked :'')  . " />\n";
            echo "</td>\n";
            echo "<td align=\"center\" class=\"boxleft pub $bottomedge\">";
          echo "<input type=\"checkbox\" value=\"$j\" name=\"a_" . $j .'"' . 
            ( ! empty ( $op['approve'] ) && ($op['approve'] & $j )? 
            $checked :'')  .  " /></td><td class=\"conf $bottomedge\">\n";
          echo '<input type="checkbox" value="'.( $j*8).'" name="a_' . ($j* 8 ) .
            '"' . ( ! empty ( $op['approve'] ) && ( $op['approve'] & ($j*8 )) ? 
            $checked :'')  . 
            " /></td><td class=\"boxright  priv $bottomedge\">\n";
          echo '<input type="checkbox" value="'.($j*64)."\" name=\"a_" . ($j*64 )  .
            '"' . ( ! empty ( $op['approve'] ) && ( $op['approve'] & ($j*64 ))? 
            $checked :'')  . " />\n";
            echo "</td>\n";
          }
          echo "</tr>\n";
        }
        echo '<tr><td colspan="2" style="text-align:right">';
        if ( $otheruser != '__default__' &&  $otheruser != '__public__' )
        echo '<input type="button" value="' . 
          translate( 'Assistant' ) . "\" onclick=\"selectAll(63);\" />&nbsp;&nbsp;";
        echo  '<input type="button" value="' . 
          translate( 'Select All' ) . "\" onclick=\"selectAll(256);\" />&nbsp;&nbsp;";
        echo  '<input type="button" value="' . 
          translate( 'Clear All' ) . "\" onclick=\"selectAll(0);\" /></td>";
        echo "<td colspan=\"9\">\n";
 
        echo '<table border="0" align="center" cellpadding="5" cellspacing="2">'.
          '<tr><td class="pub">' . 
          translate ( 'Public' ) .'</td>' .
          '<td class="conf">' . translate( 'Confidential' ) . '</td>' .
          '<td class="priv">' . translate( 'Private' ) .
          "</td></tr></table></td></tr>\n";
        echo "</tbody></table>\n";
?>
    <br /><br />
  <?php } ?>
  <input type="button" value="<?php etranslate( 'Cancel' ); ?>"
    onclick="document.location.href='access.php'" />
  <input type="submit" name="submit" value="<?php etranslate( 'Save' ); ?>" />
  </form>
<script language="javascript" type="text/javascript">
<!-- <![CDATA[
function selectAll( limit ) {
 if ( limit == 0 ) {
   document.EditOther.invite.checked = false;
   document.EditOther.email.checked = false; 
   document.EditOther.time.checked = false;
 } else {
   document.EditOther.invite.checked = true;
   document.EditOther.email.checked = true;
 }
 //clear existing values
 for ( i = 1; i <= 256; ) {
   var vname = 'v_' + i;
   document.forms['EditOther'].elements[vname].checked = false;
   var ename = 'e_' + i;
   if (document.forms['EditOther'].elements[ename])
     document.forms['EditOther'].elements[ename].checked = false;
   var aname = 'a_' + i;
   if (document.forms['EditOther'].elements[aname])
     document.forms['EditOther'].elements[aname].checked = false;
   i = parseInt(i+i);   
  } 
 for ( i = 1; i <= limit; ) {
   var vname = 'v_' + i;
   document.forms['EditOther'].elements[vname].checked = true;
   var ename = 'e_' + i;
   if (document.forms['EditOther'].elements[ename])
     document.forms['EditOther'].elements[ename].checked = true;
   var aname = 'a_' + i;
   if (document.forms['EditOther'].elements[aname])
     document.forms['EditOther'].elements[aname].checked = true;
   i = parseInt(i+i);   
  } 
}
function enableAll ( on ) {

 for ( i = 1; i <= 256; ) {
   var vname = 'v_' + i;
   document.forms['EditOther'].elements[vname].disabled = on;
   var ename = 'e_' + i;
   if (document.forms['EditOther'].elements[ename])
     document.forms['EditOther'].elements[ename].disabled = on;
   var aname = 'a_' + i;
   if (document.forms['EditOther'].elements[aname])
     document.forms['EditOther'].elements[aname].disabled = on;
   i = parseInt(i+i);   
  }
} 
//]]> -->
</script>
  <?php
  print_trailer ();
  echo "</body></html>\n";
  exit;
}
if ( $is_admin && ( empty ( $guser ) || $guser != '__default__'  ) ) {
  // If we are here... we must need to print out a list of users
  
  echo '<h2>' . translate ( 'User Access Control' ) . "</h2>\n";
  
  echo '<a title="' . translate( 'Admin' ) .
    '" class="nav" href="adminhome.php">&laquo;&nbsp;' .
    translate( 'Admin' ) . "</a><br /><br />\n";
  
  $userlist = get_my_users ();
  $nonuserlist = get_nonuser_cals ();
  ?>
  <form action="access.php" method="post" name="SelectUser">
  <select name="guser" onchange="document.SelectUser.submit()">
  <?php
  //add a DEFAULT CONFIGURATION to be used as a mask  
  echo '<option value="__default__">'.
    translate ( 'DEFAULT CONFIGURATION' )."</option>\n";
  $cnt = count ( $userlist );
  for ( $i = 0; $i < $cnt; $i++ ) {
    echo '<option value="'.$userlist[$i]['cal_login']."\">".
      $userlist[$i]['cal_fullname']."</option>\n";
  }
  $cnt = count ( $nonuserlist );
  for ( $i = 0; $i < $cnt; $i++ ) {
    $is_global = ( $nonuserlist[$i]['cal_is_public'] == 'Y'?'*':'' );
    echo '<option value="' . $nonuserlist[$i]['cal_login'] . '">'.
      $nonuserlist[$i]['cal_fullname'] . ' ' . $is_global . "</option>\n";
  }
?>
  </select>
  <input type="submit"  value="<?php etranslate( 'Go' )?>" />
  </form>
  
<?php 
} //end admin $guser !- default test
print_trailer(); ?>
</body>
</html>


<?php

// Get the list of users that the specified user can see.
function get_list_of_users ( $user )
{
  global $is_admin, $is_nonuser_admin;
  // groups not enabled... return all users
  //echo "No groups. ";
  $u = get_my_users ( $user, 'view');
  if ( $is_admin || $is_nonuser_admin ) {
    $nonusers = get_nonuser_cals ();
    $u = array_merge( $nonusers, $u );
  }
  return $u;
}

?>
