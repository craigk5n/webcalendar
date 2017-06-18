<?php
/* $Id: edit_layer.php,v 1.57.2.4 2008/03/11 13:57:24 cknudsen Exp $ */
include_once 'includes/init.php';

if ( $ALLOW_VIEW_OTHER != 'Y' ) {
  print_header ();
  echo print_not_auth (7) . print_trailer ();
  exit;
}

$layer_user = $login;
$updating_public = false;
$public = getValue ( 'public', '[01]' );

if ( $is_admin && $public == '1' && $PUBLIC_ACCESS == 'Y' ) {
  $layer_user = '__public__';
  $updating_public = true;
}

load_user_layers ( $layer_user, 1 );

$checked = 'checked="checked"';
$color = ( ! empty ( $layers[$id]['cal_color'] )
  ? $layers[$id]['cal_color'] : '#000' );
$helpStr = translate ( 'Help' );
$hiddenStr = ( $updating_public ? '
      <input type="hidden" name="public" value="1" />' : '' );
$publicStr = ( $updating_public ? translate ( $PUBLIC_ACCESS_FULLNAME ) . ' ' : '' );
$titleStr = ( empty ( $layers[$id]['cal_layeruser'] )
  ? translate ( 'Add Layer' ) : translate ( 'Edit Layer' ) );

print_header ( array ( 'js/edit_layer.php', 'js/visible.php' ) );

ob_start ();

echo <<<EOT
    <h2>{$publicStr}{$titleStr}&nbsp;
      <img src="images/help.gif" alt="{$helpStr}" class="help"
onclick="window.open( 'help_layers.php','cal_help','dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420' );" />
    </h2>
    <form action="edit_layer_handler.php" method="post"
      onsubmit="return valid_form( this );" name="prefform">{$hiddenStr}
      <table cellspacing="2" cellpadding="3">
EOT;

if ( $single_user == 'N' ) {
  $otherlist = $userlist = get_my_users ( '', 'view' );
  if ( $NONUSER_ENABLED == 'Y' ) {
    // Restrict NUC list if groups are enabled.
    $nonusers = get_my_nonusers ( $login, true, 'view' );
    $userlist = ( $NONUSER_AT_TOP == 'Y'
      ? array_merge ( $nonusers, $userlist )
      : array_merge ( $userlist, $nonusers ) );
  }
  if ( $REMOTES_ENABLED == 'Y' ) {
    $remotes = get_nonuser_cals ( $login, true );
    $userlist = ( $NONUSER_AT_TOP == 'Y'
      ? array_merge ( $remotes, $userlist )
      : array_merge ( $userlist, $remotes ) );
  }

  $num_users = $osize = $size = 0;
  $others = $users = '';

  for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
    if ( $userlist[$i]['cal_login'] != $layer_user ) {
      $size++;
      $users .= '
              <option value="' . $userlist[$i]['cal_login'] . '"'
       . ( ! empty ( $layers[$id]['cal_layeruser'] ) &&
        $layers[$id]['cal_layeruser'] == $userlist[$i]['cal_login']
        ? ' selected="selected"' : '' )
       . '>' . $userlist[$i]['cal_fullname'] . '</option>';
    }
  }

  for ( $i = 0, $cnt = count ( $otherlist ); $i < $cnt; $i++ ) {
    if ( $otherlist[$i]['cal_login'] != $layer_user ) {
      $osize++;
      $others .= '
              <option value="' . $otherlist[$i]['cal_login'] . '">'
       . $otherlist[$i]['cal_fullname'] . '</option>';
    }
  }

  if ( $size > 50 )
    $size = 15;
  elseif ( $size > 5 )
    $size = 5;

  if ( $osize > 50 )
    $osize = 15;
  elseif ( $osize > 5 )
    $osize = 5;

  if ( $size > 0 ) {
    $sourceStr = translate ( 'Source' );
    echo <<<EOT
        <tr>
          <td class="aligntop"><label for="layeruser">{$sourceStr}:</label></td>
          <td colspan="3">
            <select name="layeruser" id="layeruser" size="1">{$users}
            </select>
          </td>
        </tr>

EOT;
  }
}
$colorHtmlStr = print_color_input_html ( 'layercolor', translate ( 'Color' ),
  $color );
$duplicatesStr = translate ( 'Duplicates' );
$dupsChecked = ( ! empty ( $layers[$id]['cal_dups'] ) && $layers[$id]['cal_dups'] == 'Y'
  ? $checked : '' );
$showStr = translate ( 'Show layer events that are the same as your own' );

echo <<<EOT
        <tr>
          <td>{$colorHtmlStr}</td>
        </tr>
        <tr>
          <td class="bold">{$duplicatesStr}:</td>
          <td colspan="3"><label>
           <input type="checkbox" name="dups" value="Y" {$dupsChecked}/>&nbsp;
           {$showStr}?</label>
         </td>
        </tr>

EOT;
// If admin and adding a new layer, add ability to select other users.
if ( $is_admin && empty ( $layers[$id]['cal_layeruser'] ) && empty ( $public ) ) {
  $addStr = translate ( 'Add to Others' );
  $addmyStr = translate ( 'Add to My Calendar' );
  echo <<<EOT
        <tr>
          <td class="bold">{$addmyStr}:</td>
          <td colspan="3"><input type="checkbox" name="is_mine" {$checked}
            onclick="show_others();" /></td>
        </tr>
        <tr id="others" style="visibility: hidden;">
          <td class="aligntop"><label for="cal_login">{$addStr}:</label></td>
          <td colspan="3">
            <select name="cal_login[]" id="cal_login" size="{$osize}"
              multiple="multiple">{$others}
            </select>
          </td>
        </tr>

EOT;
}
$saveStr = translate ( 'Save' );
// If a layer already exists put a 'Delete Layer' link.
$deleteStr = ( ! empty ( $layers[$id]['cal_layeruser'] ) ? '&nbsp;&nbsp;&nbsp;
            <input type="button" value="' . translate ( 'Delete layer' )
   . '" onclick="return deleteLayer( \'del_layer.php?id=' . $id
   . ( $updating_public ? '&amp;public=1' : '' ) . '\')" />' : '' );
$hiddenStr = ( ! empty ( $layers[$id]['cal_layeruser'] ) ? '
      <input type="hidden" name="id" value="' . $id . '" />' : '' );

echo <<<EOT
        <tr>
          <td colspan="4">
            <input type="submit" value="{$saveStr}" />{$deleteStr}
          </td>
        </tr>
      </table>
      {$hiddenStr}
    </form>
EOT;

ob_end_flush ();

echo print_trailer ();

?>
