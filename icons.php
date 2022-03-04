<?php
include_once 'includes/init.php';
$icon_path = 'wc-icons/';

$can_edit = (is_dir($icon_path) &&
  ($ENABLE_ICON_UPLOADS == 'Y' || $is_admin));

if (!$can_edit)
  do_redirect('category.php');

print_header(array('js/visible.php', 'js/icons.js'), '', '', true);

$icons = [];

if ($d = dir($icon_path)) {
  while (false !== ($entry = $d->read())) {
    $icon_extensions = ['gif', 'GIF', 'png', 'PNG', 'jpg', 'JPG'];
    if (in_array(substr($entry, -3, 3), $icon_extensions)) {
      $data = '';
      // We'll compare the files to eliminate duplicates.
      $fd = @fopen($icon_path . $entry, 'rb');
      if ($fd) {
        $icons[] = $entry;
        // We only need to compare the first 1kb.
        $data .= fgets($fd, 1024);
        //$icons[md5($data)] = $entry;
      } else {
        echo "ERROR: could not open $icon_path<br>\n";
      }
      fclose($fd);
    }
  }
  $d->close();
  // Remove duplicates and replace keys with 0...n.
  //$icons = array_unique($icons);
  //Convert associative array into numeric array
  //$icons = array_values($icons);
  $title_str = translate('Click to Select');

  //echo "<pre>"; print_r($icons); echo "</pre>";
  echo '
    <table class="aligncenter">
      <tr>';
  for ($i = 0, $cnt = count($icons); $i < $cnt; $i++) {
    echo '
        <td><a href="#" onclick="sendURL(\'' . $icon_path . $icons[$i]
      . '\');" ><img src="' . $icon_path . $icons[$i] . '" title="'
      . $title_str . '" alt="' . $title_str . '" /></a></td>'
      . ($i > 0 && $i % 8 == 0 ? '
      </tr>
      <tr>' : '');
  }
}
  ?>
    </tr>
    <tr>
      <td colspan="8" class="aligncenter"><br><?php echo $title_str;?></td>
    </tr>
  </table>
  </body>
</html>