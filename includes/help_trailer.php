<?php
if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) &&
  ! empty ( $_SERVER['PHP_SELF'] ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}
if ( ! empty ( $PHP_SELF ) && preg_match ( "/\/includes\//", $PHP_SELF ) ) {
    die ( "You can't access this file directly!" );
}
?>

<hr />
<strong><?php etranslate("Go to")?>:</strong>&nbsp;<a title="<?php etranslate("Help Index")?>" href="help_index.php"><?php etranslate("Help Index")?></a>
