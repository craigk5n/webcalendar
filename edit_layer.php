<?php
/* $Id$ */
include_once 'includes/init.php';

if ( $ALLOW_VIEW_OTHER != 'Y' ) {
  print_header ();
  echo print_not_auth ();
  echo print_trailer ();
  exit;
}

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $updating_public = true;
  $layer_user = '__public__';
} else {
  $layer_user = $login;
}

load_user_layers ( $layer_user, 1 );

$INC = array('js/edit_layer.php', 'js/visible.php');
print_header($INC);
?>

<h2><?php
if ( $updating_public )
  echo translate($PUBLIC_ACCESS_FULLNAME) . ' ';

if ( ! empty ( $layers[$id]['cal_layeruser'] ) )
  etranslate( 'Edit Layer' );
else
  etranslate( 'Add Layer' );
?>&nbsp;<img src="images/help.gif" alt="<?php etranslate( 'Help' )?>" class="help" onclick="window.open ( 'help_layers.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420' );" /></h2>

<form action="edit_layer_handler.php" method="post" onsubmit="return valid_form(this);" name="prefform">

<?php if ( $updating_public ) { ?>
 <input type="hidden" name="public" value="1" />
<?php } ?>

<table>
<?php
if ( $single_user == 'N' ) {
  $userlist =  $otherlist = get_my_users ( '', 'view' );
  if ($NONUSER_ENABLED == 'Y' ) { 
    //restrict NUC list if groups are enabled
    $nonusers = get_my_nonusers ( $login , true, 'view' );
    $userlist = ($NONUSER_AT_TOP == 'Y') ? array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
  if ($REMOTES_ENABLED == 'Y' ) {
    $remotes = get_nonuser_cals ( $login, true );
    $userlist = ($NONUSER_AT_TOP == 'Y') ? array_merge($remotes, $userlist) : array_merge($userlist, $remotes);
  }  
  $num_users = 0;
  $size = 0;
  $users = '';
  for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
    if ( $userlist[$i]['cal_login'] != $layer_user ) {
      $size++;
      $users .= '<option value="' . $userlist[$i]['cal_login'] . '"';
      if ( ! empty ( $layers[$id]['cal_layeruser'] ) ) {
        if ( $layers[$id]['cal_layeruser'] == $userlist[$i]['cal_login'] )
          $users .= ' selected="selected"';
      } 
      $users .= '>' . $userlist[$i]['cal_fullname'] . "</option>\n";
    }
  }
  $osize = 0;
  $others = '';
  for ( $i = 0, $cnt = count ( $otherlist ); $i < $cnt; $i++ ) {
    if ( $otherlist[$i]['cal_login'] != $layer_user ) {
      $osize++;
      $others .= '<option value="' . $otherlist[$i]['cal_login'] . '">' .
        $otherlist[$i]['cal_fullname'] . "</option>\n";
    }
  }
  if ( $size > 50 )
    $size = 15;
  else if ( $size > 5 )
    $size = 5;
  if ( $osize > 50 )
    $osize = 15;
  else if ( $osize > 5 )
    $osize = 5;
  if ( $size >= 1 ) {
    echo"<tr><td class=\"aligntop\">\n<label for=\"layeruser\">" .
      translate( 'Source' ) . ":</label></td><td>\n";
    echo "<select name=\"layeruser\" id=\"layeruser\" size=\"1\">\n$users\n";
    echo "</select>\n";
    echo "</td></tr>\n";
  }
}
?>
<tr><td>
 <label for="layercolor"><?php etranslate( 'Color' )?>:</label></td><td>
 <input type="text" name="layercolor" id="layercolor" size="7" maxlength="7" value="<?php 
  echo empty ( $layers[$id]['cal_color'] ) ? '':  $layers[$id]['cal_color']; ?>" />
 <input type="button" onclick="selectColor('layercolor')" value="<?php 
  etranslate( 'Select' )?>..." />
</td></tr>
<tr><td class="bold">
 <?php etranslate( 'Duplicates' )?>:</td><td>
 <label><input type="checkbox" name="dups" value="Y"<?php 
  if (! empty ($layers[$id]['cal_dups']) && $layers[$id]['cal_dups'] == 'Y') 
   echo ' checked="checked"';
 ?> />&nbsp;<?php etranslate( 'Show layer events that are the same as your own' )?></label>
</td></tr>
<?php
// If admin and adding a new layer, add ability to select other users
if ( $is_admin && empty ( $layers[$id]['cal_layeruser'] ) )  {
$addStr = translate ( 'Add to Others' );
$addmyStr = translate ( 'Add to My Calendar' );
echo <<<EOT
  <tr><td class="bold"> 
  {$addmyStr}:</td><td>
  <input type="checkbox" name="is_mine"  checked="checked" onclick="show_others();" />
  </td></tr> 
  <tr id="others" style="visibility:hidden;"><td class="aligntop">
   <label for="cal_login">{$addStr}:</label></td><td>
   <select name="cal_login[]" id="cal_login" size="{$osize}" multiple="multiple" >{$others}
   </select>
  </td></tr>
EOT;
}

?>

<tr><td colspan="2">
 <input type="submit" value="<?php etranslate( 'Save' )?>" />
</td></tr>
<?php
// If a layer already exists put a 'Delete Layer' link
if ( ! empty ( $layers[$id]['cal_layeruser'] ) ) { ?>
<tr><td>
 <br /><a title="<?php etranslate( 'Delete layer' )?>" href="del_layer.php?id=<?php echo $id; if ( $updating_public ) echo '&amp;public=1'; ?>" onclick="return confirm('<?php 
etranslate( 'Are you sure you want to delete this layer?', true)?>');"><?php 
  etranslate( 'Delete layer' )?></a><br />
</td></tr>
<?php }  // end 'Delete Layer' link ?>
</table>

<?php if ( ! empty ( $layers[$id]['cal_layeruser'] ) )
 echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
?>
</form>

<?php echo print_trailer(); ?>

