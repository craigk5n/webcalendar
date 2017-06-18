<?php
/* $Id: icons.php,v 1.12.2.2 2007/08/06 02:28:30 cknudsen Exp $ */
include_once 'includes/init.php';
$icon_path = 'icons/';

$can_edit = ( is_dir ( $icon_path ) &&
  ( $ENABLE_ICON_UPLOADS == 'Y' || $is_admin ) );

if ( ! $can_edit )
  do_redirect ( 'category.php' );

print_header ( array ( 'js/visible.php' ), '', '', true );

$icons = array ();

if ( $d = dir ( $icon_path ) ) {
  while ( false !== ( $entry = $d->read () ) ) {
    if ( substr ( $entry, -3, 3 ) == 'gif' ) {
      $data = '';
      // We''ll compare the files to eliminate duplicates.
      $fd = @fopen ( $icon_path . $entry, 'rb' );
      if ( $fd ) {
        // We only need to compare the first 1kb.
        $data .= fgets ( $fd, 1024 );
        $icons[md5 ( $data )] = $entry;
      }
      fclose ( $fd );
    }
  }
  $d->close ();
  // Remove duplicates and replace keys with 0...n.
  $icons = array_unique ( $icons );
  //Convert associative array into numeric array
  $icons = array_values ( $icons );
  $title_str = translate ( 'Click to Select' );

  ?>
  <script language="JavaScript" type="text/javascript">
  <!-- <![CDATA[
  function sendURL ( url ) {
    var
      thisInput = window.opener.document.catform.urlname,
      thisPic = window.opener.document.images.urlpic,
      thistr1 = window.opener.document.getElementById ('cat_icon'),
      thistr2 = window.opener.document.getElementById ('remove_icon');
    thisInput.value = url.substring (6);
    thisPic.src = url;
    thistr1.style.visibility =
    thistr2.style.visibility = "visible";
    window.close ();
  }
  //]]> -->
  </script>

<?php
  ob_start ();
  echo '
    <table align="center" border="0">
      <tr>
        <td colspan="8" align="center"><h2>'
   . translate ( 'Current Icons on Server' ) . '</h2></td>
      </tr>
      <tr>';
  for ( $i = 0, $cnt = count ( $icons ); $i < $cnt; $i++ ) {
    echo '
        <td><a href="#" onclick="sendURL( \'' . $icon_path . $icons[$i]
     . '\' )" ><img src="' . $icon_path . $icons[$i] . '" border="0" title="'
     . $title_str . '" alt="' . $title_str . '" /></a></td>'
     . ( $i > 0 && $i % 8 == 0 ? '
      </tr>
      <tr>' : '' );
  }
  echo '
      </tr>
      <tr>
        <td colspan="8" align="center">' . $title_str . '</td>
      </tr>
    </table>
  </body>
</html>';

  ob_end_flush ();
}

?>
