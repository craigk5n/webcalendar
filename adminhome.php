<?php
/*
 $Id$

 Page Description:
  Serves as the home page for administrative functions.
 Input Parameters:
  None
 Security:
  Users will see different options available on this page.
 */
include_once 'includes/init.php';

define ( 'COLUMNS', 3 );

$style = "<style type=\"text/css\">
table.admin {
 padding: 5px;
 border: 1px solid #000000;
";
if ( function_exists ("imagepng") &&
  ( empty ($ENABLE_GRADIENTS) || $ENABLE_GRADIENTS == 'Y' ) ) {
 $style .= " background-image: url(\"gradient.php?height=300&base=ccc&percent=10\");\n";
} else {
 $style .= " background-color: #CCCCCC;\n";
}
$style .= "
}
table.admin td {
 padding: 20px;
 text-align: center;
}
.admin td a {
 padding: 10px;
 width: 125px;
 display:block;
 text-align: center;
 background-color: #CCCCCC;
 border-top: 1px solid #EEEEEE;
 border-left: 1px solid #EEEEEE;
 border-bottom: 1px solid #777777;
 border-right: 1px solid #777777;
}
.admin td a:hover {
 padding: 10px;
 width: 125px;
 display:block;
 text-align: center;
 background-color: #AAAAAA;
 border-top: 1px solid #777777;
 border-left: 1px solid #777777;
 border-bottom: 1px solid #EEEEEE;
 border-right: 1px solid #EEEEEE;
}
</style>
";
print_header('', $style);

$names = array ();
$links = array ();

if ( $is_admin && ! empty ( $SERVER_URL )
  && access_can_access_function ( ACCESS_SYSTEM_SETTINGS ) ) {
  $names[] = translate ( "Control Panel" );
  $links[] = "controlpanel.php";
}

if ($is_nonuser_admin) {
  if ( ! access_is_enabled () ||
    access_can_access_function ( ACCESS_PREFERENCES ) ) {
    $names[] = translate("Preferences");
    $links[] = "pref.php?user=$user";
  }
  
  if ( $single_user != 'Y' ) {
    if ( ! access_is_enabled () ||
      access_can_access_function ( ACCESS_ASSISTANTS ) ) {
        $names[] = translate("Assistants");
        $links[] = "assistant_edit.php?user=$user";
    }
  }
} else {

  if ( ( $is_admin && ! access_is_enabled () ) ||
    access_can_access_function ( ACCESS_SYSTEM_SETTINGS ) ) {
    $names[] = translate("System Settings");
    $links[] = "admin.php";
  }
  
  if ( ! access_is_enabled () ||
    access_can_access_function ( ACCESS_PREFERENCES ) ) {
    $names[] = translate("Preferences");
    $links[] = "pref.php";
  }
  
  if ( $is_admin ) {
    $names[] = translate("Users");
    $links[] = "users.php";
  } else {
    $names[] = translate("Account");
    $links[] = "users.php";
  }
  
  if ( access_is_enabled () &&
    access_can_access_function ( ACCESS_ACCESS_MANAGEMENT ) ) {
      $names[] = translate("User Access Control");
      $links[] = "access.php";
  }
  
  if ( $single_user != 'Y' ) {
    if ( ! access_is_enabled () ||
      access_can_access_function ( ACCESS_ASSISTANTS ) ) {
        $names[] = translate("Assistants");
        $links[] = "assistant_edit.php";
    }
  }
  
  if ( $CATEGORIES_ENABLED == 'Y' ) {
    if ( ! access_is_enabled () || 
      access_can_access_function ( ACCESS_CATEGORY_MANAGEMENT ) ) {
        $names[] = translate("Categories");
        $links[] = "category.php";
     }
  }
  
  if ( ! access_is_enabled () || 
    access_can_access_function ( ACCESS_VIEW_MANAGEMENT ) ) {
    $names[] = translate("Views");
    $links[] = "views.php";
  }
  
  if ( ! access_is_enabled () || 
    access_can_access_function ( ACCESS_LAYERS ) ) {
    $names[] = translate("Layers");
    $links[] = "layers.php";
  }
  
  if ( $REPORTS_ENABLED == 'Y' &&
    ( ! access_is_enabled () || access_can_access_function ( ACCESS_REPORT ) ) ) {
    $names[] = translate("Reports");
    $links[] = "report.php";
  }
  
  if ( $is_admin ) {
   $names[] = translate("Delete Events");
   $links[] = "purge.php";
  }
  
  // This Activity Log link shows ALL activity for ALL events, so you
  // really need to be an admin user for this.  Enabling "Activity Log"
  // in UAC just gives you access to the log for your _own_ events or
  // other events you have access to.
  if ( $is_admin && ( ! access_is_enabled () || 
    access_can_access_function ( ACCESS_ACTIVITY_LOG ) ) ) {
    $names[] = translate("Activity Log");
    $links[] = "activity_log.php";
  }
  
  if ( $is_admin && ! empty ($PUBLIC_ACCESS) && $PUBLIC_ACCESS == 'Y' ) {
   $names[] = translate("Public Preferences");
   $links[] = "pref.php?public=1";
  }
  
  if ( $is_admin && ! empty ( $PUBLIC_ACCESS ) && $PUBLIC_ACCESS == 'Y' &&
   $PUBLIC_ACCESS_CAN_ADD == 'Y' && $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL == 'Y' ) {
   $names[] = translate("Unapproved Public Events");
   $links[] = "list_unapproved.php?user=__public__";
  }
}  
?>

<h2><?php etranslate("Administrative Tools")?></h2>

<table class="admin">
<?php
 for ( $i = 0; $i < count ($names); $i++ ) {
  if ( $i % COLUMNS == 0 )
   echo "<tr>\n";
   echo "<td>";
  if ( ! empty ($links[$i]) )
   echo "<a href=\"$links[$i]\">";
  echo $names[$i];
  if ( ! empty ($links[$i]) )
   echo "</a>";
  echo "</td>\n";
  if ($i % COLUMNS == COLUMNS - 1)
   echo "</tr>\n";
 }
 if ( $i % COLUMNS != 0 )
  echo "</tr>\n";
?>
</table>

<?php print_trailer(); ?>
</body>
</html>
