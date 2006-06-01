<?php
/* $Id$ */
  include_once 'includes/init.php';
  include_once 'includes/help_list.php';  
  print_header('','','',true);
?>

<h2><?php etranslate('Help Index')?></h2>
<ul> 
<?php
  $page = 0;
  foreach ( $help_list as $key => $val ) {
    $page++;
    $transStr = translate( $key );
    $val .= '?thispage=' . $page;
    echo '<li><a title="' . $transStr . '" href="' . $val . '">' .  $transStr . '</a></li>' . "\n";
  }
?>
</ul>

<?php echo print_trailer( false, true, true ); ?>
</body>
</html>
